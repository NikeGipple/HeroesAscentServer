<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CharacterEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'character_id',
        'event_type_id',
        'type',
        'event_code',
        'title',
        'details',
        'points',
        'map_id',
        'map_type',
        'profession',
        'elite_spec',
        'race',
        'state',
        'group_type',
        'group_count',
        'commander',
        'is_login',
        'pos_x',
        'pos_y',
        'pos_z',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'commander'   => 'boolean',
        'is_login'    => 'boolean',
        'points'      => 'integer',
    ];

    public function character() { return $this->belongsTo(Character::class); }
    public function eventType() { return $this->belongsTo(EventType::class); }

    protected static function booted()
    {
        static::creating(function (CharacterEvent $event) {
            // Se non è settato event_type_id ma c'è event_code, prova a risolvere il type
            if (!$event->event_type_id && $event->event_code) {
                if ($et = EventType::where('code', $event->event_code)->first()) {
                    $event->event_type_id = $et->id;
                    // Backfill campi descrittivi se assenti
                    $event->type   = $event->type   ?: $et->category;
                    $event->title  = $event->title  ?: $et->name;
                    $event->details= $event->details?: $et->description;
                }
            }

            // Se points non è esplicitamente passato, ereditalo dal tipo evento
            // NB: usiamo array_key_exists per distinguere "non passato" da "passato con 0"
            $wasPointsProvided = array_key_exists('points', $event->getAttributes());
            if (!$wasPointsProvided && $event->event_type_id) {
                if ($et = $event->eventType ?: EventType::find($event->event_type_id)) {
                    $event->points = $et->default_points;
                    // Anche 'type/title/details' se non valorizzati
                    $event->type   = $event->type   ?: $et->category;
                    $event->title  = $event->title  ?: $et->name;
                    $event->details= $event->details?: $et->description;
                }
            }
        });

        static::created(function (CharacterEvent $event) {
            // Applica effetti al personaggio dopo la creazione
            $event->applyToCharacter();
        });
    }

    public function applyToCharacter(): void
    {
        // 1) Punteggio
        if ($this->points !== 0) {
            $this->character->increment('score', $this->points);
        }

        // 2) Squalifica se l'evento è critico
        $isCritical = $this->eventType?->is_critical ?? false;
        if ($isCritical || in_array($this->event_code, ['RULE_DOWNED_001', 'DISQUALIFIED'])) {
            $reason = $this->title ?? $this->eventType->name ?? 'Violazione grave';
            $this->character->disqualify($reason);
        }
    }

    /** Helper per creare eventi in modo centralizzato */
    public static function record(Character $character, string $eventCodeOrTypeCode, array $attrs = []): self
    {
        $et = EventType::where('code', $eventCodeOrTypeCode)->first();
        $payload = array_merge([
            'character_id'   => $character->id,
            'event_type_id'  => $et?->id,
            'event_code'     => $et?->code ?? $eventCodeOrTypeCode,
            // points NON lo mettiamo: così il boot() lo prenderà da default_points
            'detected_at'    => now(),
        ], $attrs);

        return static::create($payload);
    }
}

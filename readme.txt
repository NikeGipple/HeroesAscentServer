
http://164.92.245.131/

ssh root@164.92.245.131
h_pmw!:u39,Qd:6A

tilia.augusto@gmail.com
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


cd HeroesAscentServer
git pull origin main
tilia.augusto@gmail.com
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


builda il doker
docker build -t heroesascent .

avvia il docker
docker run -d -p 80:10000 --name heroesascent heroesascent

//lista docker
docker ps
//lista docker fermati
docker ps -a

docker logs heroesascent

docker restart heroesascent

docker stop heroesascent
docker rm heroesascent

da chiarire:
docker compose up -d --build 
oppure 
docker build -t heroesascent .


docker run -d -p 80:10000 --name heroesascent heroesascent

// per entrare nel docker
docker exec -it heroesascent bash


// riavvia apache2
service apache2 restart

docker update --restart always heroesascent
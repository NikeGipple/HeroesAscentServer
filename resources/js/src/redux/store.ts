import { configureStore } from "@reduxjs/toolkit";

export const store = configureStore({
  reducer: {}, // aggiungerai qui i tuoi slice quando servirà
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;

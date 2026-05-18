/* eslint-disable no-undef */
importScripts("https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js");

firebase.initializeApp({
  apiKey: "AIzaSyDlswcmPpRm_OzLnq0RWoE16-etEo8P17o",
  authDomain: "noti-dabd7.firebaseapp.com",
  databaseURL: "https://noti-dabd7-default-rtdb.firebaseio.com",
  projectId: "noti-dabd7",
  storageBucket: "noti-dabd7.firebasestorage.app",
  messagingSenderId: "513003762459",
  appId: "1:513003762459:web:af600d618d0cec69ab59be",
  measurementId: "G-CVBGH30BWX"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function (payload) {
  console.log("Received background message ", payload);

  const { title, body } = payload.notification;

  const notificationOptions = { body, icon: "/images/fav.svg" };

  self.registration.showNotification(title, notificationOptions);
});
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyDlswcmPpRm_OzLnq0RWoE16-etEo8P17o",
    authDomain: "noti-dabd7.firebaseapp.com",
    projectId: "noti-dabd7",
    storageBucket: "noti-dabd7.appspot.com",
    messagingSenderId: "513003762459",
    appId: "1:513003762459:web:af600d618d0cec69ab59be",
    measurementId: "G-CVBGH30BWX"
});

const messaging = firebase.messaging();

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Get FCM Token</title>

    <!-- Firebase v8 SDK -->
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js"></script>
</head>

<body>
    <h2>FCM Token Page</h2>

    <button id="get-token-btn">Get FCM Token</button>
    <p id="token-display"></p>

    <script>
        // Your Firebase config
        const firebaseConfig = {
            apiKey: "AIzaSyDlswcmPpRm_OzLnq0RWoE16-etEo8P17o",
            authDomain: "noti-dabd7.firebaseapp.com",
            projectId: "noti-dabd7",
            storageBucket: "noti-dabd7.appspot.com", // 🔧 fixed incorrect domain
            messagingSenderId: "513003762459",
            appId: "1:513003762459:web:af600d618d0cec69ab59be",
            measurementId: "G-CVBGH30BWX"
        };

        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        // Register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/api/firebase-messaging-sw.js')
                .then(function(registration) {
                    console.log('Service Worker registered:', registration);
                    messaging.useServiceWorker(registration);
                })
                .catch(function(error) {
                    console.error('Service Worker registration failed:', error);
                });
        }

        // Get token button handler
        document.getElementById('get-token-btn').addEventListener('click', function() {
            messaging.requestPermission()
                .then(() => {
                    return messaging.getToken({
                        vapidKey: "BIcB4yqMOP-QYyGSDplZaLBct8gQX2HgK9Q0QG8SAUSoMOVNjsb1Kcib27rcyNbvYP9nyxmukJV1E4KS1sE9yJc"
                    });
                })
                .then((currentToken) => {
                    if (currentToken) {
                        document.getElementById('token-display').innerText = 'FCM Token: ' + currentToken;

                        // Send token to server
                        fetch("{{ route('fcm.store') }}", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                },
                                body: JSON.stringify({
                                    fcm_token: currentToken
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('FCM token stored successfully');
                                } else {
                                    alert('Error: ' + data.error);
                                }
                            })
                            .catch(error => {
                                console.error('Error storing FCM token:', error);
                            });
                    } else {
                        console.error('No FCM token available');
                    }
                })
                .catch((error) => {
                    console.error('Error getting permission or token:', error);
                });
        });
    </script>
</body>

</html>

<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Authentication - ELMS</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .camera-card {
            width: 520px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .camera-box {
            background: #111827;
            border-radius: 12px;
            overflow: hidden;
            margin: 20px 0;
        }

        video,
        canvas {
            width: 100%;
            display: block;
        }

        canvas {
            display: none;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        button,
        a {
            padding: 11px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }

        .primary {
            background: #eb2535;
            color: white;
        }

        .secondary {
            background: #e2e8f0;
            color: #111827;
        }

        .success {
            background: #16a34a;
            color: white;
        }

        #status {
            margin-top: 18px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="camera-card">

    <h1>Camera Authentication</h1>
    <p>Use your webcam to capture your face for identity verification.</p>

    <div class="camera-box">
        <video id="video" autoplay playsinline></video>
        <canvas id="canvas"></canvas>
    </div>

    <div class="actions">

        <button class="primary" type="button" onclick="startCamera()">
            Open Camera
        </button>

        <button class="secondary" type="button" onclick="captureFace()">
            Capture Face
        </button>

        <button class="success" type="button" onclick="verifyFace()">
            Verify Identity
        </button>

        <a href="index.php" class="secondary">
            Back to Dashboard
        </a>

    </div>

    <div id="status"></div>

</div>

<script>

let stream = null;
let faceCaptured = false;

async function startCamera() {

    const video = document.getElementById("video");
    const status = document.getElementById("status");

    try {

        stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: false
        });

        video.srcObject = stream;

        status.textContent = "Camera opened successfully.";
        status.style.color = "#16a34a";

    } catch (error) {

        console.error(error);

        status.textContent =
            "Camera permission denied or camera unavailable.";

        status.style.color = "#dc2626";
    }
}

function captureFace() {

    const video = document.getElementById("video");
    const canvas = document.getElementById("canvas");
    const status = document.getElementById("status");

    if (!stream) {
        status.textContent = "Open the camera first.";
        status.style.color = "#dc2626";
        return;
    }

    const context = canvas.getContext("2d");

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    context.drawImage(
        video,
        0,
        0,
        canvas.width,
        canvas.height
    );

    video.style.display = "none";
    canvas.style.display = "block";

    faceCaptured = true;

    status.textContent = "Face captured successfully.";
    status.style.color = "#16a34a";
}

function verifyFace() {

    const status = document.getElementById("status");

    if (!faceCaptured) {
        status.textContent = "Capture your face first.";
        status.style.color = "#dc2626";
        return;
    }

    status.textContent = "Identity verified successfully.";
    status.style.color = "#16a34a";

    setTimeout(() => {
        window.location.href = "index.php";
    }, 1500);
}

</script>

</body>
</html>
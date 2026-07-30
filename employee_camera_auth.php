<?php

session_start();

if (!isset($_SESSION["employee_id"])) {
    header("Location: employee_login.php");
    exit();
}

$_SESSION["camera_verified"] = false;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Employee Camera Verification - ELMS</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.card {
    width: 560px;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
}

h1 {
    margin-top: 0;
}

.employee-name {
    color: #64748b;
    margin-bottom: 20px;
}

.camera-box {
    background: #111827;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
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
    gap: 10px;
    flex-wrap: wrap;
}

button,
a {
    border: none;
    padding: 11px 16px;
    border-radius: 8px;
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

<div class="card">

    <h1>Employee Camera Verification</h1>

    <p class="employee-name">
        Employee:
        <strong>
            <?= htmlspecialchars(
                $_SESSION["employee_name"] ?? "Employee"
            ) ?>
        </strong>
    </p>

    <div class="camera-box">

        <video
            id="video"
            autoplay
            playsinline
        ></video>

        <canvas id="canvas"></canvas>

    </div>

    <div class="actions">

        <button
            class="primary"
            type="button"
            onclick="startCamera()"
        >
            Open Camera
        </button>

        <button
            class="secondary"
            type="button"
            onclick="captureFace()"
        >
            Capture Face
        </button>

        <button
            class="success"
            type="button"
            onclick="verifyIdentity()"
        >
            Verify Identity
        </button>

        <a
            href="employee_logout.php"
            class="secondary"
        >
            Cancel
        </a>

    </div>

    <div id="status"></div>

</div>

<script>

let stream = null;
let faceCaptured = false;

async function startCamera() {

    const video =
        document.getElementById("video");

    const status =
        document.getElementById("status");

    try {

        stream =
            await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });

        video.srcObject = stream;

        status.textContent =
            "Camera opened successfully.";

        status.style.color =
            "#16a34a";

    } catch (error) {

        console.error(error);

        status.textContent =
            "Unable to access camera.";

        status.style.color =
            "#dc2626";
    }
}

function captureFace() {

    const video =
        document.getElementById("video");

    const canvas =
        document.getElementById("canvas");

    const status =
        document.getElementById("status");

    if (!stream) {

        status.textContent =
            "Open the camera first.";

        status.style.color =
            "#dc2626";

        return;
    }

    const context =
        canvas.getContext("2d");

    canvas.width =
        video.videoWidth;

    canvas.height =
        video.videoHeight;

    context.drawImage(
        video,
        0,
        0,
        canvas.width,
        canvas.height
    );

    video.style.display =
        "none";

    canvas.style.display =
        "block";

    faceCaptured = true;

    status.textContent =
        "Face captured successfully.";

    status.style.color =
        "#16a34a";
}

async function verifyIdentity() {

    const status =
        document.getElementById("status");

    if (!faceCaptured) {

        status.textContent =
            "Capture your face first.";

        status.style.color =
            "#dc2626";

        return;
    }

    status.textContent =
        "Verifying identity...";

    status.style.color =
        "#ca8a04";

    try {

        const response =
            await fetch(
                "employee_camera_verify.php",
                {
                    method: "POST"
                }
            );

        const data =
            await response.json();

        if (data.success) {

            status.textContent =
                "Identity verified successfully.";

            status.style.color =
                "#16a34a";

            if (stream) {

                stream
                    .getTracks()
                    .forEach(
                        track => track.stop()
                    );
            }

            setTimeout(() => {

                window.location.href =
                    "employee_dashboard.php";

            }, 1000);

        } else {

            status.textContent =
                data.message ||
                "Verification failed.";

            status.style.color =
                "#dc2626";
        }

    } catch (error) {

        console.error(error);

        status.textContent =
            "Verification request failed.";

        status.style.color =
            "#dc2626";
    }
}

</script>

</body>

</html>
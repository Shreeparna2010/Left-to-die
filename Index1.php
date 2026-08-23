<?php

/* =========================================================

   DISCORD WEBHOOK

   Paste your webhook URL between the quotes.

   ========================================================= */

$WEBHOOK_URL = "https://discord.com/api/webhooks/1540963621755027537/QSgOqWWVE3c2wuxF-2goO_ESMexHXmp5OgSUq-iTXKsEKF0XGhBuj54F_HZrTl_-CI8y";

/* =========================================================

   SEND TO DISCORD

   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    header("Content-Type: application/json");

    $message = trim($_POST["message"] ?? "");

    $image = $_FILES["image"] ?? null;

    if (

        $message === "" &&

        (!$image || $image["error"] !== UPLOAD_ERR_OK)

    ) {

        http_response_code(400);

        echo json_encode([

            "error" => "Message or image is required."

        ]);

        exit;

    }

    /*

     * Build Discord multipart/form-data request.

     * This allows text + image in the SAME Discord message.

     */

    $payload = [

        "content" => $message

    ];

    $postData = [

        "payload_json" => json_encode($payload)

    ];

    if (

        $image &&

        $image["error"] === UPLOAD_ERR_OK

    ) {

        // Only allow images

        $allowed = [

            "image/jpeg",

            "image/png",

            "image/gif",

            "image/webp"

        ];

        if (!in_array($image["type"], $allowed)) {

            http_response_code(400);

            echo json_encode([

                "error" => "Only image files are allowed."

            ]);

            exit;

        }

        // Discord webhook attachment

        $postData["files[0]"] =

            new CURLFile(

                $image["tmp_name"],

                $image["type"],

                $image["name"]

            );

    }

    $ch = curl_init($WEBHOOK_URL);

    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_POST => true,

        CURLOPT_POSTFIELDS => $postData,

        CURLOPT_TIMEOUT => 30

    ]);

    $response = curl_exec($ch);

    if ($response === false) {

        http_response_code(500);

        echo json_encode([

            "error" => curl_error($ch)

        ]);

        curl_close($ch);

        exit;

    }

    $httpCode =

        curl_getinfo(

            $ch,

            CURLINFO_HTTP_CODE

        );

    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {

        http_response_code($httpCode);

        echo json_encode([

            "error" =>

                "Discord returned HTTP " .

                $httpCode

        ]);

        exit;

    }

    echo json_encode([

        "success" => true

    ]);

    exit;

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta

    name="viewport"

    content="width=device-width, initial-scale=1.0"

>

<title>Discord Messenger</title>

<style>

* {

    box-sizing: border-box;

}

body {

    margin: 0;

    height: 100vh;

    overflow: hidden;

    background: #313338;

    color: #dbdee1;

    font-family:

        Arial,

        Helvetica,

        sans-serif;

}

.app {

    height: 100vh;

    display: flex;

    flex-direction: column;

}

/* HEADER */

.header {

    height: 55px;

    display: flex;

    align-items: center;

    padding: 0 16px;

    background: #2b2d31;

    border-bottom:

        1px solid #1e1f22;

    font-weight: bold;

}

.hash {

    color: #949ba4;

    font-size: 23px;

    margin-right: 8px;

}

/* MESSAGES */

.messages {

    flex: 1;

    overflow-y: auto;

    padding:

        15px 12px;

}

.message {

    display: flex;

    gap: 12px;

    padding:

        7px 5px;

    border-radius: 6px;

}

.message:hover {

    background: #2e3035;

}

.avatar {

    width: 40px;

    height: 40px;

    min-width: 40px;

    border-radius: 50%;

    background: #5865f2;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 10px;

    font-weight: bold;

}

.messageBody {

    min-width: 0;

}

.username {

    color: white;

    font-weight: bold;

}

.time {

    color: #949ba4;

    font-size: 11px;

    margin-left: 7px;

}

.content {

    margin-top: 3px;

    white-space: pre-wrap;

    word-break: break-word;

}

.content img {

    display: block;

    max-width:

        min(420px, 80vw);

    max-height: 420px;

    margin-top: 8px;

    border-radius: 8px;

}

/* COMPOSER */

.composer {

    padding:

        8px 12px 15px;

    background: #313338;

}

.fileName {

    color: #949ba4;

    font-size: 12px;

    padding:

        3px 8px;

}

.inputBox {

    display: flex;

    align-items: flex-end;

    gap: 6px;

    padding: 6px;

    background: #383a40;

    border-radius: 8px;

}

textarea {

    flex: 1;

    min-height: 40px;

    max-height: 140px;

    resize: none;

    background: transparent;

    border: 0;

    outline: 0;

    color: #dbdee1;

    font-size: 15px;

    padding: 9px;

}

textarea::placeholder {

    color: #949ba4;

}

.attach {

    width: 40px;

    height: 40px;

    border: 0;

    border-radius: 50%;

    background: #4e5058;

    color: white;

    font-size: 22px;

}

.send {

    width: 44px;

    height: 40px;

    border: 0;

    border-radius: 7px;

    background: #5865f2;

    color: white;

    font-size: 18px;

}

.send:disabled {

    opacity: .5;

}

input[type="file"] {

    display: none;

}

#status {

    position: fixed;

    top: 65px;

    right: 10px;

    padding:

        8px 12px;

    background: #1e1f22;

    border-radius: 7px;

    font-size: 13px;

    display: none;

}

</style>

</head>

<body>

<div class="app">

    <div class="header">

        <span class="hash">#</span>

        general

    </div>

    <div

        id="messages"

        class="messages"

    ></div>

    <div class="composer">

        <div

            id="fileName"

            class="fileName"

        ></div>

        <div class="inputBox">

            <input

                id="image"

                type="file"

                accept="image/*"

            >

            <button

                id="attach"

                class="attach"

                type="button"

            >

                +

            </button>

            <textarea

                id="message"

                placeholder="Message #general"

                rows="1"

            ></textarea>

            <button

                id="send"

                class="send"

                type="button"

            >

                ➤

            </button>

        </div>

    </div>

</div>

<div id="status"></div>

<script>

const message =

    document.getElementById("message");

const image =

    document.getElementById("image");

const send =

    document.getElementById("send");

const attach =

    document.getElementById("attach");

const messages =

    document.getElementById("messages");

const fileName =

    document.getElementById("fileName");

const statusBox =

    document.getElementById("status");

/* Open image picker */

attach.onclick = () => {

    image.click();

};

/* Show selected filename */

image.onchange = () => {

    if (image.files.length) {

        fileName.textContent =

            "📎 " +

            image.files[0].name;

    } else {

        fileName.textContent = "";

    }

};

/* Status popup */

function showStatus(text) {

    statusBox.textContent = text;

    statusBox.style.display =

        "block";

    setTimeout(() => {

        statusBox.style.display =

            "none";

    }, 2500);

}

/* Add sent message to local UI */

function addMessage(text, file) {

    const row =

        document.createElement("div");

    row.className = "message";

    const avatar =

        document.createElement("div");

    avatar.className = "avatar";

    avatar.textContent = "YOU";

    const body =

        document.createElement("div");

    body.className =

        "messageBody";

    const username =

        document.createElement("span");

    username.className =

        "username";

    username.textContent =

        "You";

    const time =

        document.createElement("span");

    time.className =

        "time";

    time.textContent =

        new Date().toLocaleTimeString(

            [],

            {

                hour: "2-digit",

                minute: "2-digit"

            }

        );

    body.appendChild(username);

    body.appendChild(time);

    const content =

        document.createElement("div");

    content.className =

        "content";

    if (text) {

        content.appendChild(

            document.createTextNode(text)

        );

    }

    if (file) {

        const img =

            document.createElement("img");

        img.src =

            URL.createObjectURL(file);

        content.appendChild(img);

    }

    body.appendChild(content);

    row.appendChild(avatar);

    row.appendChild(body);

    messages.appendChild(row);

    messages.scrollTop =

        messages.scrollHeight;

}

/* Send */

async function sendMessage() {

    const text =

        message.value.trim();

    const file =

        image.files[0];

    if (!text && !file)

        return;

    send.disabled = true;

    const form =

        new FormData();

    form.append(

        "message",

        text

    );

    if (file) {

        form.append(

            "image",

            file

        );

    }

    try {

        const response =

            await fetch(

                window.location.href,

                {

                    method: "POST",

                    body: form

                }

            );

        const result =

            await response.json();

        if (!response.ok) {

            throw new Error(

                result.error ||

                "Failed to send"

            );

        }

        addMessage(

            text,

            file

        );

        message.value = "";

        image.value = "";

        fileName.textContent = "";

        showStatus(

            "Message sent ✓"

        );

    } catch (error) {

        showStatus(

            "❌ " +

            error.message

        );

    }

    send.disabled = false;

}

/* Send button */

send.onclick =

    sendMessage;

/* Enter sends, Shift+Enter makes a new line */

message.addEventListener(

    "keydown",

    event => {

        if (

            event.key === "Enter" &&

            !event.shiftKey

        ) {

            event.preventDefault();

            sendMessage();

        }

    }

);

</script>

</body>

</html>
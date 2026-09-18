<?php
require_once dirname(__FILE__) . '/no_cache.php';   /* ส่ง header กันแคช ต้องอยู่ก่อน output ใด ๆ */
ob_start();
include('nav.php');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | SUPAVUT GROUP</title>
    <link href="./src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link rel="icon" type="image/png" href="./img/3si.png">
    <link rel="apple-touch-icon" href="./img/pwa/icon-192.png">
    <link rel="manifest" href="./site.webmanifest">
    <meta name="theme-color" content="#424242">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@100..700&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <?php /* คุมขนาดการแสดงผลให้เท่ากันทุกเครื่อง (ดู zoom_lock.php) */ ?>
    <?php require_once dirname(__FILE__) . '/zoom_lock.php'; ?>

    <style>
        body.login-body {
            min-height: var(--screen-h);
            background:
                radial-gradient(circle at 12% 8%, rgba(66, 66, 66, 0.12), transparent 42%),
                radial-gradient(circle at 88% 92%, rgba(93, 93, 93, 0.08), transparent 45%),
                #f3f6fb;
        }
        .login-card {
            border: 1px solid #e4e9f1;
            box-shadow: 0 24px 60px rgba(16, 23, 42, 0.10);
        }
        .login-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
        }
        .login-input {
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }
        .login-input:focus {
            border-color: #424242 !important;
            box-shadow: 0 0 0 4px rgba(66, 66, 66, 0.18);
            outline: none;
        }
        .login-submit {
            background: #424242;
            color: #ffffff !important;
            transition: transform 160ms ease, box-shadow 160ms ease, filter 160ms ease;
        }
        .login-submit:hover {
            filter: brightness(1.06);
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(26, 26, 26, 0.18);
        }

        /* ── AI Assistant (AssistiveTouch style) ────────── */
        .ai-fab {
            position: fixed;
            right: 24px;
            bottom: 28px;
            z-index: 60;
            width: 74px;
            height: 74px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 50%;
            background: #fff;
            color: #fff;
            box-shadow: 0 14px 30px rgba(16, 23, 42, 0.18);
            cursor: grab;
            touch-action: none;
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .ai-fab-logo {
            width: 62px;
            height: 62px;
            display: block;
            object-fit: contain;
        }

        .ai-fab::before {
            content: "";
            position: absolute;
            inset: -5px;
            border-radius: 50%;
            border: 2px solid rgba(239, 98, 33, 0.28);
            animation: aiPulse 2.4s ease-out infinite;
        }

        @keyframes aiPulse {
            0% { transform: scale(0.9); opacity: 0.7; }
            100% { transform: scale(1.35); opacity: 0; }
        }

        .ai-fab:hover {
            transform: scale(1.06);
            box-shadow: 0 18px 38px rgba(16, 23, 42, 0.24);
        }

        .ai-fab.dragging {
            cursor: grabbing;
            transform: scale(1.1);
        }

        .ai-fab.dragging::before {
            animation: none;
            opacity: 0;
        }

        .ai-chat {
            position: fixed;
            right: 24px;
            bottom: 96px;
            z-index: 60;
            width: min(346px, calc(100vw - 32px));
            height: min(470px, calc(var(--screen-h) - 130px));
            display: none;
            flex-direction: column;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #e4e9f1;
            border-radius: 20px;
            box-shadow: 0 28px 64px rgba(16, 23, 42, 0.3);
        }

        .ai-chat.is-open {
            display: flex;
            animation: aiChatPop 200ms ease;
        }

        @keyframes aiChatPop {
            from { opacity: 0; transform: translateY(8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .ai-chat-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            background: #424242;
            color: #ffffff;
        }

        .ai-chat-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ai-chat-avatar {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #fff;
        }

        .ai-logo,
        .ai-msg-logo {
            width: 84%;
            height: 84%;
            display: block;
            object-fit: contain;
        }

        .ai-chat-title strong {
            display: block;
            font-size: 1.1rem;
        }

        .ai-chat-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.7rem;
            opacity: 0.9;
        }

        .ai-chat-status::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #fde047;
        }

        .ai-chat-close {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            cursor: pointer;
        }

        .ai-chat-close:hover {
            background: rgba(255, 255, 255, 0.32);
        }

        .ai-chat-body {
            flex: 1;
            overflow: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
            background: #f7f9fc;
        }

        .ai-msg {
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }

        .ai-msg.user {
            flex-direction: row-reverse;
        }

        .ai-msg-ava {
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #f1f1f1;
            font-size: 0.84rem;
        }

        .ai-msg-ava.bot-logo {
            background: #fff;
            border: 1px solid #e4e9f1;
        }

        .ai-bubble {
            max-width: 78%;
            padding: 10px 12px;
            border-radius: 14px;
            font-size: 0.84rem;
            line-height: 1.55;
            background: #ffffff;
            border: 1px solid #e4e9f1;
            color: #10172a;
        }

        .ai-msg.user .ai-bubble {
            background: #424242;
            border-color: #424242;
            color: #ffffff;
        }

        .ai-chat-input {
            display: flex;
            gap: 8px;
            padding: 12px;
            border-top: 1px solid #e4e9f1;
            background: #ffffff;
        }

        .ai-chat-input input {
            flex: 1;
            min-height: 42px;
            padding: 0 14px;
            border: 1px solid #e4e9f1;
            border-radius: 999px;
            background: #f7f9fc;
            color: #10172a;
            font: inherit;
        }

        .ai-chat-input button {
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 50%;
            background: #424242;
            color: #ffffff;
            cursor: pointer;
            transition: background 160ms ease;
        }

        .ai-chat-input button:hover {
            background: #333333;
        }
    </style>
</head>
<body class="login-body flex flex-col" style="font-family: 'Kanit', serif;">

    <main class="flex-1">
        <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto lg:py-0 min-h-screen">
            <div class="login-card w-full bg-white rounded-2xl md:mt-0 sm:max-w-md xl:p-0">
                <div class="p-7 space-y-5 md:space-y-6 sm:p-9">
                    <div class="flex flex-col items-center text-center gap-3">
                        <img src="./img/3si.png" alt="Supavut Group logo" class="login-logo">
                        <div>
                            <h1 class="text-lg font-bold leading-tight tracking-tight text-gray-900 md:text-xl">
                                เข้าสู่ระบบผู้ดูแล
                            </h1>
                            <p class="text-xs text-gray-500 mt-1">SUPAVUT GROUP · Intranet Portal</p>
                        </div>
                    </div>
                    <form method="POST">
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-gray-700">รหัสผ่าน</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input type="password" name="password" id="password" class="login-input bg-gray-50 border border-gray-300 text-gray-900 rounded-lg block w-full p-2.5 pl-10 pr-10" required autofocus>
                                <span id="togglePassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:cursor-pointer">
                                    <i class='fa-solid fa-eye'></i>
                                </span>
                            </div>
                        </div>
                        <button type="submit" class="login-submit w-full text-white font-medium rounded-lg text-sm px-5 py-2.5 text-center hover:cursor-pointer mt-6">เข้าสู่ระบบ</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- ── AI Assistant (draggable) ───────────────────── -->
    <button id="aiFab" class="ai-fab" type="button" aria-label="ผู้ช่วย AI">
        <img class="ai-fab-logo" src="img/si5.jpg" alt="">
    </button>

    <div id="aiChat" class="ai-chat" role="dialog" aria-label="ผู้ช่วย AI">
        <div class="ai-chat-head">
            <div class="ai-chat-title">
                <span class="ai-chat-avatar">
                    <img class="ai-logo" src="img/si5.jpg" alt="">
                </span>
                <div>
                    <strong>SI Assistant</strong>
                    <span class="ai-chat-status">กำลังพัฒนา</span>
                </div>
            </div>
            <button class="ai-chat-close" id="aiChatClose" type="button" aria-label="ปิด">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
            </button>
        </div>
        <div class="ai-chat-body" id="aiChatBody">
            <div class="ai-msg bot">
                <span class="ai-msg-ava bot-logo"><img class="ai-msg-logo" src="img/si5.jpg" alt=""></span>
                <div class="ai-bubble">สวัสดีครับ ผมคือผู้ช่วย AI ของ Supavut Group<br>ฟีเจอร์แชทนี้กำลังอยู่ในช่วงพัฒนา เร็วๆ นี้จะพร้อมให้คุยได้จริงครับ</div>
            </div>
        </div>
        <form class="ai-chat-input" id="aiChatForm">
            <input id="aiChatText" type="text" placeholder="พิมพ์ข้อความ..." autocomplete="off">
            <button type="submit" aria-label="ส่ง">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </button>
        </form>
    </div>

    <?php


    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
        $password = $_POST['password'];
        if ($password == 'SM_yanatchara68663') {
            header("Location: admin.php");
            exit();
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                    Swal.fire({
                            icon: 'error',
                            title: 'รหัสผ่านไม่ถูกต้อง',
                            timer: 1500
                        });
            </script>";
        }
    }

    include('footer.php');
    ob_end_flush();
    ?>

    <script>
        const togglePassword = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("password");
        // toggle
        togglePassword.addEventListener("click", function() {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);

            this.innerHTML = type === "password" ?
                "<i class='fa-solid fa-eye'></i>" :
                "<i class='fa-solid fa-eye-slash'></i>";
        });
    </script>

    <script>
        'use strict';
        // ── AI Assistant: draggable floating button + mini chat ──
        (function aiAssistant() {
            const fab = document.getElementById('aiFab');
            const chat = document.getElementById('aiChat');
            const closeBtn = document.getElementById('aiChatClose');
            const form = document.getElementById('aiChatForm');
            const input = document.getElementById('aiChatText');
            const chatBody = document.getElementById('aiChatBody');

            if (!fab || !chat) {
                return;
            }

            let dragging = false;
            let moved = false;
            let startX = 0;
            let startY = 0;
            let originLeft = 0;
            let originTop = 0;

            function placeChat() {
                const rect = fab.getBoundingClientRect();
                const w = chat.offsetWidth;
                const h = chat.offsetHeight;
                let left = rect.right - w;
                let top = rect.top - h - 12;
                if (top < 12) {
                    top = rect.bottom + 12;
                }
                left = Math.max(12, Math.min(left, window.innerWidth - w - 12));
                top = Math.max(12, Math.min(top, window.innerHeight - h - 12));
                chat.style.left = `${left}px`;
                chat.style.top = `${top}px`;
                chat.style.right = 'auto';
                chat.style.bottom = 'auto';
            }

            function openChat() {
                chat.classList.add('is-open');
                placeChat();
                if (input) {
                    input.focus();
                }
            }

            function closeChat() {
                chat.classList.remove('is-open');
            }

            function toggleChat() {
                if (chat.classList.contains('is-open')) {
                    closeChat();
                } else {
                    openChat();
                }
            }

            function appendMessage(message, who) {
                const wrap = document.createElement('div');
                wrap.className = `ai-msg ${who}`;
                const avatar = who === 'user'
                    ? '<span class="ai-msg-ava">🙂</span>'
                    : '<span class="ai-msg-ava bot-logo"><img class="ai-msg-logo" src="img/si5.jpg" alt=""></span>';
                wrap.innerHTML = `${avatar}<div class="ai-bubble"></div>`;
                wrap.querySelector('.ai-bubble').textContent = message;
                chatBody.appendChild(wrap);
                chatBody.scrollTop = chatBody.scrollHeight;
            }

            fab.addEventListener('pointerdown', (event) => {
                dragging = true;
                moved = false;
                const rect = fab.getBoundingClientRect();
                originLeft = rect.left;
                originTop = rect.top;
                startX = event.clientX;
                startY = event.clientY;
                fab.classList.add('dragging');
                fab.setPointerCapture(event.pointerId);
            });

            fab.addEventListener('pointermove', (event) => {
                if (!dragging) {
                    return;
                }
                const dx = event.clientX - startX;
                const dy = event.clientY - startY;
                if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
                    moved = true;
                }
                let left = originLeft + dx;
                let top = originTop + dy;
                left = Math.max(8, Math.min(left, window.innerWidth - fab.offsetWidth - 8));
                top = Math.max(8, Math.min(top, window.innerHeight - fab.offsetHeight - 8));
                fab.style.left = `${left}px`;
                fab.style.top = `${top}px`;
                fab.style.right = 'auto';
                fab.style.bottom = 'auto';
            });

            fab.addEventListener('pointerup', (event) => {
                dragging = false;
                fab.classList.remove('dragging');
                try {
                    fab.releasePointerCapture(event.pointerId);
                } catch (error) {
                    // pointer already released
                }
                if (!moved) {
                    toggleChat();
                } else if (chat.classList.contains('is-open')) {
                    placeChat();
                }
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', closeChat);
            }

            if (form) {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const value = (input.value || '').trim();
                    if (!value) {
                        return;
                    }
                    appendMessage(value, 'user');
                    input.value = '';
                    setTimeout(() => {
                        appendMessage('ขออภัยครับ ฟีเจอร์แชท AI ยังอยู่ในช่วงพัฒนา 🛠️ เร็วๆ นี้จะพร้อมให้บริการครับ', 'bot');
                    }, 500);
                });
            }

            window.addEventListener('resize', () => {
                if (chat.classList.contains('is-open')) {
                    placeChat();
                }
            });
        })();
    </script>

</body>

</html>

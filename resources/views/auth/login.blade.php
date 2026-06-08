<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - ERP Produksi</title>
    <!-- Font Awesome CDN for social media icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 2;
            padding: 1;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #1c1c1c; /* Dark background for neon effect */
            overflow: hidden;
            color: #fff;
        }

        /* Neon glow background effect */
        body::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 600px;
            height: 600px;
            background: linear-gradient(45deg, #4f46e5, #06b6d4);
            border-radius: 50%;
            filter: blur(150px);
            opacity: 0.5;
            animation: pulseGlow 5s ease-in-out infinite alternate;
        }

        @keyframes pulseGlow {
            0% { transform: translate(-50%, -50%) scale(1); opacity: 0.4; }
            100% { transform: translate(-50%, -50%) scale(1.1); opacity: 0.6; }
        }

        /* Interactive Mouse Glow Effect */
        .cursor-glow {
            position: fixed;
            top: 0;
            left: 0;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.25) 0%, rgba(79, 70, 229, 0.1) 40%, transparent 60%);
            border-radius: 80%;
            pointer-events: none;
            transform: translate(-50%, -50%);
            z-index: 1;
            transition: opacity 0.3s ease;
            opacity: 0;
        }

        body:hover .cursor-glow {
            opacity: 1;
        }

        .container {
            position: relative;
            background: rgba(255, 255, 255, 0.05); /* Glassmorphism effect */
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            width: 90%;
            max-width: 400px;
            overflow: hidden; 
            display: flex;
            z-index: 10;
        }

        .container p {
            color: #ccc;
            font-size: 14px;
            margin: 15px 0;
            line-height: 1.5;
        }

        .container a {
            color: #fff;
            font-size: 13px;
            text-decoration: none;
            margin: 10px 0 20px;
            font-weight: 500;
            align-self: flex-end; /* Align to the right */
        }

        .container a:hover {
            text-decoration: underline;
        }

        .container button {
            border-radius: 10px;
            border: 1px solid #4f46e5;
            background-color: #4f46e5;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            padding: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform 80ms ease-in, background-color 0.3s ease, box-shadow 0.3s ease;
            margin-top: 5px;
            width: 100%;
        }

        .container button:active {
            transform: scale(0.95);
        }

        .container button:focus {
            outline: none;
        }
        .container button:hover {
            background-color: #7c3aed;
            border-color: #7c3aed;
            color: #ffffff;
            box-shadow: 0 0 20px #7c3aed;
        }

        .container form {
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 40px;
            width: 100%;
            text-align: center;
        }

        .container input {
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            padding: 14px 15px;
            margin: 8px 0;
            width: 100%;
            color: #fff;
            font-size: 14px;
            outline: none;
            border: 1px solid transparent;
            transition: border-color 0.3s ease;
        }

        .container input::placeholder {
            color: #ccc;
        }

        .container input:focus {
            border-color: #312e81;
        }

        h1 {
            font-weight: bold;
            margin: 0;
            color: #fff;
            text-shadow: 0 0 10px rgba(79, 70, 229, 0.5);
        }

        .form-container {
            width: 100%;
            z-index: 2;
        }


        .social-container {
            margin: 20px 0;
        }

        .social-container div.icon-circle {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 5px;
            height: 40px;
            width: 40px;
            color: #fff;
            transition: all 0.3s ease;
        }

        .error-msg {
            color: #ffb3c1;
            font-size: 12px;
            margin-top: -5px;
            margin-bottom: 5px;
            text-align: left;
            width: 100%;
        }



        /* Responsive Design */
        @media (max-width: 480px) {
            .container form {
                padding: 30px 20px;
            }
            .cursor-glow {
                display: none; /* Disable glow on mobile to save performance/battery */
            }
        }
    </style>
</head>
<body>

    <div class="container" id="container">
        
        <!-- Sign In Form -->
        <div class="form-container">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <h1>Masuk</h1>
                
                <div class="social-container">
                    <div class="icon-circle"><i class="fas fa-industry"></i></div>
                    <div class="icon-circle"><i class="fas fa-boxes"></i></div>
                    <div class="icon-circle"><i class="fas fa-chart-line"></i></div>
                </div>
                
                <p>Gunakan kredensial sistem Anda</p>
                
                @if (session('status'))
                    <p style="color: #06b6d4;">{{ session('status') }}</p>
                @endif

                @if ($errors->any())
                    <p style="color: #ef4444;">{{ $errors->first() }}</p>
                @endif

                <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                
                <input type="password" name="password" placeholder="Kata Sandi" required autocomplete="current-password" />
                


                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Lupa Kata Sandi?</a>
                @endif

                <button type="submit">Masuk</button>
            </form>
        </div>
        <!-- Overlay panel has been removed -->
        
    </div>

    <script>
        // Interactive Mouse Glow Logic
        const cursorGlow = document.createElement('div');
        cursorGlow.className = 'cursor-glow';
        document.body.appendChild(cursorGlow);

        document.addEventListener('mousemove', (e) => {
            // Use requestAnimationFrame for smoother performance
            requestAnimationFrame(() => {
                cursorGlow.style.left = `${e.clientX}px`;
                cursorGlow.style.top = `${e.clientY}px`;
            });
        });

        document.addEventListener('mouseleave', () => {
            cursorGlow.style.opacity = '0';
        });

        document.addEventListener('mouseenter', () => {
            cursorGlow.style.opacity = '1';
        });
    </script>
</body>
</html>

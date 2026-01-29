<?php
require_once 'includes/security.php';
secure_session_start();

// Force Line in-app browser to open external browser
if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Line') !== false && strpos($_SERVER['QUERY_STRING'], 'openExternalBrowser=1') === false) {
    if (!headers_sent()) {
        $sep = strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?';
        header("Location: " . $_SERVER['REQUEST_URI'] . $sep . "openExternalBrowser=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>
        <?php echo SITE_NAME; ?>
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo LOGO_URL; ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sarabun': ['Sarabun', 'sans-serif'],
                    },
                    colors: {
                        'mcu-blue': '#1a237e',
                        'mcu-pink': '#d81b60',
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #fcfcfc;
            color: #1f2937;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .input-field {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-field:focus {
            border-color: #ec4899;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
        }

        .btn-primary {
            background-color: #1a237e;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #0d1654;
        }

        .btn-pink {
            background-color: #d81b60;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .btn-pink:hover {
            background-color: #ad1457;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
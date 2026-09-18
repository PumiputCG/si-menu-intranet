<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="./img/logo.png">
    <link href="./src/output.css?v=<?= time() ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@100..700&family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body style="font-family: 'Kanit', serif;">

    <nav class="bg-gray-900 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto" src="img/logo.png" alt="Logo">
                        <span class="ml-2 text-xl font-bold">SIC - Inspection</span>
                    </div>
                    <div class="hidden md:flex md:ml-6 space-x-8">
                        <a href="report.php" class="text-gray-300 hover:text-white border-b-2 border-transparent hover:border-gray-300 px-1 pt-1 inline-flex items-center text-sm font-medium">
                            Report
                        </a>
                        <a href="dashboard.php" class="text-gray-300 hover:text-white border-b-2 border-transparent hover:border-gray-300 px-1 pt-1 inline-flex items-center text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="export2.php" class="text-gray-300 hover:text-white border-b-2 border-transparent hover:border-gray-300 px-1 pt-1 inline-flex items-center text-sm font-medium">
                            Export
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="login.php" class="text-gray-300 hover:text-white border-b-2 border-transparent hover:border-gray-300 px-1 pt-1 inline-flex items-center text-sm font-medium">
                        Admin
                    </a>
                </div>

            </div>
        </div>
    </nav>

</body>

</html>
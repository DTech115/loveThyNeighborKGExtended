<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Foodway | Love Thy Neighbor Community Food Pantry</title>
        <link rel="stylesheet" href="css/layoutInfo.css">
        <link rel="stylesheet" href="header.css">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet">
        <style>* { font-family: Quicksand, sans-serif; }</style>
    </head>
<body class="bg-gray-100">
<div class="min-h-screen flex flex-col">
<?php
$isFoodwayPage = true;
require_once('header.php');
?>
    <div class="px-6 pt-4">
        <p style="font-size: 50px; text-align:center;"> Welcome to Foodway!</p>
    </div>
    <main class="flex flex-1 items-center justify-center p-6">

    <!-- Button row: add more buttons here later -->
    <div class="flex flex-wrap items-center justify-center gap-8">

        <a href="#"
            class="group relative overflow-hidden px-16 py-10 text-3xl font-bold text-white
                bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                active:translate-y-0 transition duration-300">
        <!-- shine sweep on hover -->
        <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                    group-hover:left-[150%] transition-all duration-700"></span>
        <span class="relative">Record Food Out</span>
        </a>

    </div>
</main>
</div>
</body>
</html>
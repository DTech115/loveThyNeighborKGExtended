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
        <p style="font-size: 32px; text-align:center;"> Welcome to Foodway!</p>
    </div>
    <main class="flex flex-1 items-center justify-center p-6">

    <!-- TWO columns for Food Out & Food In -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 w-full max-w-5xl">
    

        <!-- left column for Food Out -->
        <div class="flex flex-col items-center gap-8">
            <h2 class="text-3xl font-bold text-center">Food Out</h2>

            <!-- add more buttons here -->
            <!-- shopping -->
            <a href="foodwayShopping.php"
                class="group isolate relative flex w-72 min-h-28 items-center justify-center
                overflow-hidden whitespace-normal break-words px-6 py-6
                    text-center text-3xl font-bold text-white
                    bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                    rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                    hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                    active:translate-y-0 transition duration-300">
            <!-- shine sweep on hover -->
            <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                        group-hover:left-[150%] transition-all duration-700"></span>
            <span class="relative">Shopping</span>
            </a>

            <!-- waste [pig fud] -->
            <a href="foodwayWaste.php"
                class="group isolate relative flex w-72 min-h-28 items-center justify-center
                overflow-hidden whitespace-normal break-words px-6 py-6
                    text-center text-3xl font-bold text-white
                    bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                    rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                    hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                    active:translate-y-0 transition duration-300">
            <!-- shine sweep on hover -->
            <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                        group-hover:left-[150%] transition-all duration-700"></span>
            <span class="relative">Waste [Pig Fud]</span>
            </a>

            <!-- in-house use -->
            <a href="foodwayInHouseUse.php"
                class="group isolate relative flex w-72 min-h-28 items-center justify-center
                overflow-hidden whitespace-normal break-words px-6 py-6
                    text-center text-3xl font-bold text-white
                    bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                    rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                    hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                    active:translate-y-0 transition duration-300">
            <!-- shine sweep on hover -->
            <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                        group-hover:left-[150%] transition-all duration-700"></span>
            <span class="relative">In-House Use</span>
            </a>

            <!-- donations from us -->
            <a href="foodwayDonationFromUs.php"
                class="group isolate relative flex w-72 min-h-28 items-center justify-center
                overflow-hidden whitespace-normal break-words px-6 py-6
                    text-center text-3xl font-bold text-white
                    bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                    rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                    hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                    active:translate-y-0 transition duration-300">
            <!-- shine sweep on hover -->
            <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                        group-hover:left-[150%] transition-all duration-700"></span>
            <span class="relative">Donations From Us</span>
            </a>

        </div>

        <!-- right column for Food In -->
        <div class="flex flex-col items-center gap-8">
            <h2 class="text-3xl font-bold text-center">Food In</h2>

            <!-- add more buttons here -->
             <!-- retail donation -->
            <a href="foodwayRetailDonation.php"
                class="group isolate relative flex w-72 min-h-28 items-center justify-center
                overflow-hidden whitespace-normal break-words px-6 py-6
                    text-center text-3xl font-bold text-white
                    bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                    rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                    hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                    active:translate-y-0 transition duration-300">
            <!-- shine sweep on hover -->
            <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                        group-hover:left-[150%] transition-all duration-700"></span>
            <span class="relative">Retail Donation</span>
            </a>

            <!-- FRFB -->
            <a href="foodwayFRFB.php"
                class="group isolate relative flex w-72 min-h-28 items-center justify-center
                overflow-hidden whitespace-normal break-words px-6 py-6
                    text-center text-3xl font-bold text-white
                    bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                    rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                    hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                    active:translate-y-0 transition duration-300">
            <!-- shine sweep on hover -->
            <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                        group-hover:left-[150%] transition-all duration-700"></span>
            <span class="relative">FRFB</span>
            </a>

            <!-- Donation from... -->
            <a href="foodwayDonationFrom.php"
                class="group isolate relative flex w-72 min-h-28 items-center justify-center
                overflow-hidden whitespace-normal break-words px-6 py-6
                    text-center text-3xl font-bold text-white
                    bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                    rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                    hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                    active:translate-y-0 transition duration-300">
            <!-- shine sweep on hover -->
            <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                        group-hover:left-[150%] transition-all duration-700"></span>
            <span class="relative">Donation from...</span>
            </a>

            <a href="foodwayPurchasedLTN.php"
                class="group isolate relative flex w-72 min-h-28 items-center justify-center
                overflow-hidden whitespace-normal break-words px-6 py-6
                    text-center text-3xl font-bold text-white
                    bg-gradient-to-b from-[rgb(30,100,200)] to-[rgb(0,74,173)]
                    rounded-2xl shadow-xl shadow-blue-900/40 ring-1 ring-white/20
                    hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-900/50
                    active:translate-y-0 transition duration-300">
            <!-- shine sweep on hover -->
            <span class="absolute inset-y-0 -left-full w-1/2 -skew-x-12 bg-white/30 blur-sm
                        group-hover:left-[150%] transition-all duration-700"></span>
            <span class="relative">Purchased by LTN</span>
            </a>
    </div>
    </main>
</div>
</body>
</html>
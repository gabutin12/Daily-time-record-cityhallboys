<?php
// get_tracks.php

// Define the path to the music folder
$musicFolder = __DIR__ . '/music/'; // Adjust this path based on your folder structure

// Check if the music folder exists
if (!is_dir($musicFolder)) {
    echo json_encode(['error' => 'Music folder does not exist']);
    exit;
}

// Scan the music folder for MP3 files
$files = glob($musicFolder . '*.mp3');
$tracks = [];

// Filter for MP3 files
foreach ($files as $file) {
    $tracks[] = [
        'title' => pathinfo($file, PATHINFO_FILENAME), // Use the file name (without extension) as the title
        'file' => 'music/' . basename($file) // Relative path to the MP3 file
    ];
}

// Return the list of tracks as JSON
echo json_encode(['tracks' => $tracks]);

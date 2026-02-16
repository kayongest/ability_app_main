<?php
// includes/qr_generator.php - IMPROVED VERSION

// Prevent loading multiple times
if (defined('QR_GENERATOR_LOADED')) {
    return;
}
define('QR_GENERATOR_LOADED', true);

if (!function_exists('generateQRCodeForItem')) {
    /**
     * Generate QR code for an item
     */
    function generateQRCodeForItem($item_id, $item_name, $serial_number, $stock_location)
    {
        global $rootDir; // Use the root directory from create.php

        if (empty($rootDir)) {
            $rootDir = dirname(__DIR__);
        }

        $qrDir = $rootDir . '/qrcodes';

        // Create directory if needed
        if (!is_dir($qrDir)) {
            if (!mkdir($qrDir, 0755, true)) {
                error_log("Failed to create QR directory: $qrDir");
                return false;
            }
        }

        // Check if directory is writable
        if (!is_writable($qrDir)) {
            error_log("QR directory not writable: $qrDir");
            return false;
        }

        $filename = 'qr_' . $item_id . '.png';
        $relativePath = 'qrcodes/' . $filename;
        $fullPath = $qrDir . '/' . $filename;

        // Check if QR already exists
        if (file_exists($fullPath)) {
            return $relativePath;
        }

        // Generate QR data - OPTIMIZED to reduce size and prevent overflow
        // Using shortened keys and minimal data to stay under 864 bit limit
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $url = $protocol . $host . '/ability_app_main/items/view.php?id=' . $item_id;

        // Option 1: Minimal data approach (recommended)
        $qrData = [
            'i' => (int)$item_id,
            'n' => substr($item_name, 0, 20),  // Limit name to 20 chars
            's' => $serial_number
        ];

        // Option 2: URL-only approach (alternative - uncomment to use)
        // $qrDataString = $url;

        $qrDataString = json_encode($qrData, JSON_UNESCAPED_SLASHES);

        // Use Google Charts API with fallbacks
        $googleUrl = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" . urlencode($qrDataString) . "&choe=UTF-8";

        // Create context for file_get_contents
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'timeout' => 10,
                'header' => "User-Agent: Mozilla/5.0\r\n"
            ]
        ]);

        // Try file_get_contents first
        $qrImage = @file_get_contents($googleUrl, false, $context);

        if ($qrImage === false || strlen($qrImage) < 100) {
            // Fallback to cURL if available
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $googleUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                $qrImage = curl_exec($ch);
                curl_close($ch);
            }

            // Final fallback: local QR generation
            if (($qrImage === false || strlen($qrImage) < 100) && file_exists($rootDir . '/vendor/autoload.php')) {
                require_once $rootDir . '/vendor/autoload.php';

                try {
                    // Use Endroid QR Code if installed via composer
                    $qrCode = \Endroid\QrCode\QrCode::create($qrDataString)
                        ->setSize(300)
                        ->setMargin(10);

                    $writer = new \Endroid\QrCode\Writer\PngWriter();
                    $result = $writer->write($qrCode);
                    $qrImage = $result->getString();
                } catch (Exception $e) {
                    error_log("Local QR generation failed: " . $e->getMessage());
                    return false;
                }
            }
        }

        if ($qrImage && strlen($qrImage) > 100) {
            if (file_put_contents($fullPath, $qrImage)) {
                // Set proper permissions
                chmod($fullPath, 0644);
                return $relativePath;
            } else {
                error_log("Failed to save QR image to: $fullPath");
            }
        } else {
            error_log("QR image generation failed or returned invalid data");
        }

        return false;
    }
}

// Keep other functions for compatibility
if (!function_exists('getQRCodeForItem')) {
    function getQRCodeForItem($item_id)
    {
        $rootDir = dirname(__DIR__);
        $qrDir = $rootDir . '/qrcodes';
        $filename = 'qr_' . $item_id . '.png';
        $fullPath = $qrDir . '/' . $filename;

        if (file_exists($fullPath)) {
            return 'qrcodes/' . $filename;
        }
        return false;
    }
}

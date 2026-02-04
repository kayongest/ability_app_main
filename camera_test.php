<!-- Add to your scan.php file -->
<div style="text-align: center; margin: 20px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
    <h4>📱 Open on Mobile:</h4>
    <p>Scan this QR code with your Android SD60 camera</p>
    <div id="mobileQrCode"></div>
    <p style="font-size: 12px; color: #666; margin-top: 10px;">
        Or go to: <code id="mobileUrl"></code>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
// Generate QR code for mobile access
function generateMobileQrCode() {
    // Get current URL
    const currentUrl = window.location.href;
    
    // Replace localhost with your actual IP
    const localIp = '192.168.1.100'; // CHANGE THIS TO YOUR IP
    const mobileUrl = currentUrl.replace(/localhost:\d+|127\.0\.0\.1:\d+/, localIp + ':8000');
    
    // Display the URL
    document.getElementById('mobileUrl').textContent = mobileUrl;
    
    // Generate QR code
    QRCode.toCanvas(document.getElementById('mobileQrCode'), mobileUrl, {
        width: 200,
        margin: 2,
        color: {
            dark: '#4361ee',
            light: '#ffffff'
        }
    });
}

// Run when page loads
document.addEventListener('DOMContentLoaded', generateMobileQrCode);
</script>
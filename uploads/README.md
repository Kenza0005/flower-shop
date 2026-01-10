# Uploads Directory

This directory is used for file uploads in the application.

## Security Notes

### Vulnerable Version
- No file type restrictions
- Original filenames preserved
- No size limits
- PHP execution enabled
- **DANGER**: Can upload web shells and malicious files

### Secure Version  
- File type whitelist (images only)
- Random filenames generated
- Size limits enforced
- PHP execution disabled via .htaccess
- Files re-encoded to strip metadata
- MIME type validation

## Test Files

For testing file upload vulnerabilities, try uploading:

### Malicious Files (Vulnerable Version Only)
- `shell.php` - Simple web shell
- `image.php.jpg` - Double extension bypass
- `.htaccess` - Server configuration override

### Legitimate Files
- `test.jpg` - Valid image file
- `test.png` - Valid image file
- `test.gif` - Valid image file

**⚠️ Warning**: Only test malicious uploads in isolated environments!
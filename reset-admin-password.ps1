$ErrorActionPreference = 'Stop'
$securePassword = $null
$secureConfirmation = $null
$passwordPointer = [IntPtr]::Zero
$confirmationPointer = [IntPtr]::Zero

try {
    $securePassword = Read-Host 'New admin password' -AsSecureString
    $secureConfirmation = Read-Host 'Confirm new admin password' -AsSecureString

    $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    $confirmationPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureConfirmation)
    $plainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    $plainConfirmation = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($confirmationPointer)

    if ($plainPassword -cne $plainConfirmation) {
        throw 'Passwords did not match; no database changes were made.'
    }

    $plainPassword | & php (Join-Path $PSScriptRoot 'reset-admin-password.php')
    if ($LASTEXITCODE -ne 0) {
        throw 'The password reset failed. Check the error above.'
    }
} finally {
    if ($passwordPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
    }
    if ($confirmationPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($confirmationPointer)
    }
    Remove-Variable securePassword, secureConfirmation, plainPassword, plainConfirmation -ErrorAction SilentlyContinue
}

#Requires -RunAsAdministrator
# https://serverfault.com/questions/95431/in-a-powershell-script-how-can-i-check-if-im-running-with-administrator-privil

# A simple PowerShell script to enable/disable the PostgreSQL service on my local Windows machine
$sname = "postgresql-x64-9.5"
$state = (Get-Service $sname).Status
if ($state -eq "Stopped") {
	Start-Service $sname
} else {
	Stop-Service $sname
}
if ($state -ne (Get-Service $sname).Status) {
	Write-Host "Succesfully $((Get-Service $sname).Status.ToString().ToLower()) $sname."
} else {
	Write-Host "Failed to execute!"
	Write-Host "Current status is '$($state.ToString().ToLower())'."
}
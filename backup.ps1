# Configurações do Backup
$Date = Get-Date -Format "yyyy-MM-dd_HH-mm"
$BackupRoot = "C:\Backups_Sistema_Recenseadores"
if (!(Test-Path $BackupRoot)) { New-Item -ItemType Directory -Path $BackupRoot }

# Caminhos de Origem
$LiveFolder = "C:\xampp\htdocs\recenseadores"
$MysqlDumpExe = "C:\xampp\mysql\bin\mysqldump.exe"
$DbName = "sistema_recenseadores"
$DbUser = "root"

# Arquivos de Destino
$DbBackupFile = "$BackupRoot\banco_$Date.sql"
$FilesZipPath = "$BackupRoot\arquivos_$Date.zip"

Write-Host "INICIANDO BACKUP DO SISTEMA..." -ForegroundColor Cyan

# 1. Backup do Banco de Dados
Write-Host "[1/2] Exportando banco de dados ($DbName)..." -ForegroundColor Yellow
if (Test-Path $MysqlDumpExe) {
    # Usando cmd.exe para evitar que o PowerShell mude o encoding do output (isso evita quebrar os acentos)
    cmd.exe /c "`"$MysqlDumpExe`" -u $DbUser --default-character-set=utf8mb4 $DbName > `"$DbBackupFile`""
    if ($LASTEXITCODE -eq 0) {
        Write-Host " -> Banco exportado com sucesso!" -ForegroundColor Green
    } else {
        Write-Host " -> FALHA ao exportar banco. Verifique se o MySQL esta rodando." -ForegroundColor Red
    }
} else {
    Write-Host " -> mysqldump.exe nao encontrado em $MysqlDumpExe" -ForegroundColor Red
}

# 2. Compactação dos Arquivos (Zip)
Write-Host "[2/2] Compactando pasta de arquivos e uploads..." -ForegroundColor Yellow
if (Test-Path $LiveFolder) {
    try {
        # Excluindo a pasta de sessões se houver muitos arquivos
        Compress-Archive -Path "$LiveFolder\*" -DestinationPath $FilesZipPath -Force
        Write-Host " -> Arquivos compactados com sucesso!" -ForegroundColor Green
    } catch {
        Write-Host " -> FALHA ao compactar arquivos: $_" -ForegroundColor Red
    }
} else {
    Write-Host " -> Pasta de origem nao encontrada: $LiveFolder" -ForegroundColor Red
}

Write-Host "`nBACKUP CONCLUIDO!" -ForegroundColor Cyan
Write-Host "Local dos arquivos: $BackupRoot" -ForegroundColor White
Write-Host "Aperte qualquer tecla para fechar."

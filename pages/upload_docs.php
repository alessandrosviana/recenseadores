<?php
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['document'])) {
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    $filename = $_FILES['document']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $newFilename = uniqid() . '_' . $filename;
        $destination = $uploadDir . $newFilename;

        if (move_uploaded_file($_FILES['document']['tmp_name'], $destination)) {
            $stmt = $pdo->prepare("INSERT INTO documents (user_id, document_type, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'identidade', $destination]);
            $message = '<div class="status-badge status-approved">Documento enviado com sucesso! Aguarde a análise.</div>';
        } else {
            $message = '<div class="status-badge status-rejected">Erro ao salvar arquivo.</div>';
        }
    } else {
        $message = '<div class="status-badge status-rejected">Formato inválido. Apenas PDF, JPG e PNG.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload de Documentos - RecenseaTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
    <header>
        <nav>
            <div class="logo">RecenseaTech</div>
            <div class="nav-links">
                <a href="/logout.php" class="btn btn-outline">Sair</a>
            </div>
        </nav>
    </header>
    <main class="container">
        <div class="card" style="max-width: 600px; margin: 2rem auto;">
            <h2 class="text-center mb-4">Envio de Documentos</h2>
            <p class="text-center" style="color: var(--text-muted); margin-bottom: 2rem;">
                Para finalizar seu cadastro, precisamos que envie um documento de identificação (RG ou CNH).
            </p>

            <?php if (!empty($message))
                echo "<div class='text-center mb-4'>$message</div>"; ?>

            <form action="" method="post" enctype="multipart/form-data" class="text-center">
                <div class="form-group"
                    style="padding: 2rem; border: 2px dashed rgba(255,255,255,0.2); border-radius: var(--radius);">
                    <i class="fas fa-cloud-upload-alt"
                        style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <p class="mb-4">Arraste seu arquivo aqui ou clique para selecionar</p>
                    <input type="file" name="document" required style="display: none;" id="file-upload">
                    <label for="file-upload" class="btn btn-outline">Selecionar Arquivo</label>
                    <p id="file-name" class="mt-4" style="color: var(--accent);"></p>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Enviar Documento</button>
            </form>

            <div class="mt-4 text-center">
                <a href="dashboard.php" style="font-size: 0.875rem;">Pular por enquanto (Cadastro ficará pendente)</a>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('file-upload').addEventListener('change', function (e) {
            if (e.target.files[0]) {
                document.getElementById('file-name').textContent = e.target.files[0].name;
            }
        });
    </script>
    <?php include '../includes/footer.php'; ?>
</body>

</html>
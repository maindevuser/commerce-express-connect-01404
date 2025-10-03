<?php
include __DIR__ . '/config.php';

require_once __DIR__ . '/../../controllers/AuthController.php';
use Controllers\AuthController;

if (!AuthController::isAdmin()) {
    header('Location: ../../login.php');
    exit();
}

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/SyncClass.php';
use Models\SyncClass;

$database = new \Database();
$db = $database->getConnection();
$syncClassModel = new SyncClass($db);

$action = $_GET['sub_action'] ?? '';
$classId = $_GET['class_id'] ?? '';

// Manejar creación
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $meeting_link = trim($_POST['meeting_link'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    if (!empty($title) && !empty($meeting_link) && !empty($start_date) && !empty($end_date)) {
        $syncClassModel->title = $title;
        $syncClassModel->description = $description;
        $syncClassModel->price = $price;
        $syncClassModel->meeting_link = $meeting_link;
        $syncClassModel->start_date = $start_date;
        $syncClassModel->end_date = $end_date;
        $syncClassModel->is_active = 1;
        
        if ($syncClassModel->create()) {
            $success_message = "Clase sincrónica creada exitosamente";
        } else {
            $error_message = "Error al crear la clase";
        }
    } else {
        $error_message = "Todos los campos requeridos deben ser completados";
    }
}

// Manejar actualización
if ($action === 'edit' && $classId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $meeting_link = trim($_POST['meeting_link'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $is_active = intval($_POST['is_active'] ?? 1);
    
    if (!empty($title) && !empty($meeting_link) && !empty($start_date) && !empty($end_date)) {
        $syncClassModel->id = $classId;
        $syncClassModel->title = $title;
        $syncClassModel->description = $description;
        $syncClassModel->price = $price;
        $syncClassModel->meeting_link = $meeting_link;
        $syncClassModel->start_date = $start_date;
        $syncClassModel->end_date = $end_date;
        $syncClassModel->is_active = $is_active;
        
        if ($syncClassModel->update()) {
            $success_message = "Clase sincrónica actualizada exitosamente";
        } else {
            $error_message = "Error al actualizar la clase";
        }
    } else {
        $error_message = "Todos los campos requeridos deben ser completados";
    }
}

// Manejar eliminación
if ($action === 'delete' && $classId) {
    if ($syncClassModel->delete($classId)) {
        $success_message = "Clase sincrónica eliminada exitosamente";
    } else {
        $error_message = "Error al eliminar la clase";
    }
}

// Obtener todas las clases
$syncClasses = $syncClassModel->readAll();

// Obtener clase para editar
$editClass = null;
if ($action === 'edit' && $classId && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editClass = $syncClassModel->readOne($classId);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clases Sincrónicas - Admin</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/public/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sync-classes-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .class-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .classes-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .classes-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .classes-table th,
        .classes-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .classes-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="sync-classes-container">
        <div class="page-header">
            <h1><i class="fas fa-video"></i> Gestión de Clases Sincrónicas</h1>
            <p>Crea y gestiona las clases en vivo para tus estudiantes</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="class-form">
            <h2><?php echo $editClass ? 'Editar' : 'Nueva'; ?> Clase Sincrónica</h2>
            <form method="POST" action="?page=admin&action=sync-classes&sub_action=<?php echo $editClass ? 'edit&class_id=' . $editClass['id'] : 'create'; ?>">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Título *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($editClass['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Descripción</label>
                        <textarea name="description"><?php echo htmlspecialchars($editClass['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Precio (USD) *</label>
                        <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($editClass['price'] ?? '0'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Link de Reunión *</label>
                        <input type="url" name="meeting_link" value="<?php echo htmlspecialchars($editClass['meeting_link'] ?? ''); ?>" placeholder="https://zoom.us/..." required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Inicio *</label>
                        <input type="datetime-local" name="start_date" value="<?php echo $editClass ? date('Y-m-d\TH:i', strtotime($editClass['start_date'])) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Finalización *</label>
                        <input type="datetime-local" name="end_date" value="<?php echo $editClass ? date('Y-m-d\TH:i', strtotime($editClass['end_date'])) : ''; ?>" required>
                    </div>
                    
                    <?php if ($editClass): ?>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="is_active">
                            <option value="1" <?php echo ($editClass['is_active'] == 1) ? 'selected' : ''; ?>>Activa</option>
                            <option value="0" <?php echo ($editClass['is_active'] == 0) ? 'selected' : ''; ?>>Inactiva</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editClass ? 'Actualizar' : 'Crear'; ?> Clase
                    </button>
                    <?php if ($editClass): ?>
                    <a href="?page=admin&action=sync-classes" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="classes-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Precio</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($syncClasses)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">
                            No hay clases sincrónicas creadas aún
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($syncClasses as $class): ?>
                    <tr>
                        <td><?php echo $class['id']; ?></td>
                        <td><?php echo htmlspecialchars($class['title']); ?></td>
                        <td>$<?php echo number_format($class['price'], 2); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($class['start_date'])); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($class['end_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $class['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $class['is_active'] ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="?page=admin&action=sync-classes&sub_action=edit&class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-edit">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="?page=admin&action=sync-classes&sub_action=delete&class_id=<?php echo $class['id']; ?>" 
                                   class="btn btn-sm btn-delete" 
                                   onclick="return confirm('¿Estás seguro de eliminar esta clase?')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

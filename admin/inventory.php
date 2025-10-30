<?php
include '../includes/functions.php';

// Redirect if not admin
if (!isAdmin()) {
    header('Location: /amwali-closet/');
    exit();
}

// Include database connection
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            // Add new product
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $size = $_POST['size'];
            $stock_quantity = intval($_POST['stock_quantity']);
            $image_url = trim($_POST['image_url']);
            
            $query = "INSERT INTO products (name, description, price, category_id, size, stock_quantity, image_url) 
                     VALUES (:name, :description, :price, :category_id, :size, :stock_quantity, :image_url)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':size', $size);
            $stmt->bindParam(':stock_quantity', $stock_quantity);
            $stmt->bindParam(':image_url', $image_url);
            
            if ($stmt->execute()) {
                $message = 'Product added successfully!';
            }
            
        } elseif ($action === 'edit') {
            // Update product
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $category_id = intval($_POST['category_id']);
            $size = $_POST['size'];
            $stock_quantity = intval($_POST['stock_quantity']);
            $image_url = trim($_POST['image_url']);
            $available = isset($_POST['available']) ? 1 : 0;
            
            $query = "UPDATE products SET name = :name, description = :description, price = :price, 
                     category_id = :category_id, size = :size, stock_quantity = :stock_quantity, 
                     image_url = :image_url, available = :available WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':size', $size);
            $stmt->bindParam(':stock_quantity', $stock_quantity);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':available', $available);
            
            if ($stmt->execute()) {
                $message = 'Product updated successfully!';
            }
            
        } elseif ($action === 'delete') {
            // Delete product
            $id = intval($_POST['id']);
            
            $query = "DELETE FROM products WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $message = 'Product deleted successfully!';
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all products with categories
try {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories for dropdown
    $categories_stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Amwali Closet</title>
    <link rel="stylesheet" href="/amwali-closet/assets/css/style.css">
    <style>
        .inventory-management {
            padding: 2rem 0;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .admin-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .add-product-btn {
            background: #10b981;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .products-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .products-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .products-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .status-available {
            color: #10b981;
            font-weight: 600;
        }
        
        .status-unavailable {
            color: #ef4444;
            font-weight: 600;
        }
        
        .edit-btn, .delete-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }
        
        .edit-btn {
            background: #3b82f6;
            color: white;
        }
        
        .delete-btn {
            background: #ef4444;
            color: white;
        }
        
        /* Modal Styles */
        .modal-form {
            max-width: 600px;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="inventory-management">
            <div class="container">
                <div class="page-header">
                    <h1>Inventory Management</h1>
                    <p>Manage your product catalog</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="admin-actions">
                    <div></div>
                    <button class="add-product-btn" onclick="openAddModal()">+ Add New Product</button>
                </div>
                
                <div class="products-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Price/Day</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach($products as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="product-image">
                                                <?php if($product['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                <?php else: ?>
                                                    <div style="background: #e2e8f0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #64748b;">
                                                        No Image
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo $product['size']; ?></td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['stock_quantity']; ?></td>
                                        <td>
                                            <?php if($product['available']): ?>
                                                <span class="status-available">Available</span>
                                            <?php else: ?>
                                                <span class="status-unavailable">Unavailable</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="edit-btn" onclick="openEditModal(<?php echo $product['id']; ?>)">Edit</button>
                                            <button class="delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: #64748b;">
                                        No products found. Add your first product!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content modal-form">
            <span class="close-modal" onclick="closeProductModal()">&times;</span>
            <h2 id="modalTitle">Add New Product</h2>
            <form id="productForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">
                
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price per Day ($)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="size">Size</label>
                    <select id="size" name="size" required>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="available" name="available" value="1" checked> 
                        Available for rent
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeProductModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New Product';
        document.getElementById('formAction').value = 'add';
        document.getElementById('productForm').reset();
        document.getElementById('productId').value = '';
        document.getElementById('available').checked = true;
        document.getElementById('productModal').style.display = 'block';
    }
    
    function openEditModal(productId) {
        // In a real implementation, you would fetch the product data via AJAX
        // For now, we'll redirect to a edit page or show a message
        alert('Edit functionality will fetch product data for ID: ' + productId);
        // You can implement AJAX to populate the form with existing data
    }
    
    function deleteProduct(productId) {
        if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            form.appendChild(actionInput);
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = productId;
            form.appendChild(idInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function closeProductModal() {
        document.getElementById('productModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('productModal');
        if (event.target === modal) {
            closeProductModal();
        }
    }
    </script>
</body>
</html>
<?php
$current = basename($_SERVER['SCRIPT_NAME']);
$tabs = [
    'index.php'      => 'Stats',
    'categories.php' => 'Categories',
    'quizzes.php'    => 'Quizzes',
    'users.php'      => 'Users',
];
?>
<h2 class="mb-3">Admin Panel</h2>
<ul class="nav nav-pills mb-4">
    <?php foreach ($tabs as $file => $label): ?>
        <li class="nav-item">
            <a class="nav-link <?= $current === $file ? 'active' : '' ?>" href="<?= e($file) ?>"><?= e($label) ?></a>
        </li>
    <?php endforeach; ?>
</ul>
<?php show_flash(); ?>
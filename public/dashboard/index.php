<?php
declare(strict_types=1);
$title       = 'Dashboard';
$headerTitle = 'Dashboard';
$footerLinks = [
  ['href' => '/datenschutz/',          'label' => 'Datenschutz'],
  ['href' => '/interessensabwaegung/', 'label' => 'Interessensabwägung'],
  ['href' => '/bib/',                  'label' => 'QR-Bibliothek'],
];
require __DIR__ . '/../../includes/head.php';
?>

<main>
  <h2>Hier entsteht das Dashboard.</h2>
</main>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

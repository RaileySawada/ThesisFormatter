<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="<?= $meta["author"] ?>">
    <meta name="description" content="<?= $meta["meta_desc"] ?>">
    <meta name="keywords" content="<?= $meta["meta_keywords"] ?>">
    <title>Thesis Formatter</title>
    <link rel="icon" href="<?= FAVICON ?>" type="image/x-icon" sizes="32x32 16x16">
    <script src="<?= TAILWIND ?>"></script>
    <script src="<?= FONTAWESOME ?>" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= STYLES_CSS ?>">
  </head>
  <body>
    <section class="relative flex min-h-screen max-h-[max-content] w-full">
      <?php if(!empty($user_id) && isset($user_role)) include SIDEBAR; ?>
      <main class="flex flex-col flex-1 bg-white w-full md:w-[calc(100%-108px)] lg:w-[calc(100%-312px)]">
        <section class="relative mb-auto">
          <?php require TOAST ?>

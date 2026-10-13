<?php require 'lib.php';
$r = request($_GET['t'] ?? '');
if (!$r || !owns($r) || $r['status'] !== 'approved') go('index.php');

// Example data for the prototype dashboard (all fictional).
$name = strtoupper(str_replace(['.', '_'], ' ', explode('@', $r['email'])[0]));
$work = [
  ['HCI 210 - Human-Computer Interaction', 'Usability Test Plan', '10/14/2026 11:59 PM', 30],
  ['HCI 210 - Human-Computer Interaction', 'Prototype Walkthrough Video', '10/16/2026 11:59 PM', 20],
  ['NET 220 - Network Fundamentals', 'Subnetting Problem Set', '10/15/2026 11:59 PM', 25],
  ['DB 230 - Database Design', 'ER Diagram: Library System', '10/18/2026 11:59 PM', 40],
  ['WEB 240 - Web Development', 'Responsive Portfolio Page', '10/21/2026 11:59 PM', 50],
];
$courses = [
  ['HCI 210 - Human-Computer Interaction', '88%', '#2f6f5e'],
  ['NET 220 - Network Fundamentals', '81%', '#3a5a9a'],
  ['DB 230 - Database Design', '92%', '#8a4b2e'],
  ['WEB 240 - Web Development', '76%', '#6b3f8a'],
  ['MATH 250 - Discrete Mathematics', '84%', '#9a6a12'],
  ['GE 105 - Ethics in Technology', 'N/A', '#4f5b66'],
];
$news = [
  ['Project checkpoint this Friday', 'Prof. Ramon Dizon', 'HCI 210', 'Oct 8, 2026', 'Please bring your updated prototype and test notes to class.'],
  ['Midterm grades posted', 'Prof. Liza Manalo', 'DB 230', 'Oct 5, 2026', 'Review your midterm grade and message me within one week if you have concerns.'],
  ['Lab moved to Room 402', 'Engr. Paolo Cruz', 'NET 220', 'Sep 30, 2026', 'This week\'s lab session will be held in Room 402 because of maintenance.'],
];
$people = [
  ['Prof. Ramon Dizon', 'HCI 210 - Human-Computer Interaction'],
  ['Prof. Liza Manalo', 'DB 230 - Database Design'],
  ['Engr. Paolo Cruz', 'NET 220 - Network Fundamentals'],
  ['Ms. Andrea Santos', 'WEB 240 - Web Development'],
];
$ini = fn($n) => implode('', array_map(fn($w) => $w[0], array_slice(array_values(array_filter(explode(' ', preg_replace('/^(Prof|Engr|Ms)\. /', '', $n)))), 0, 2)));
?><!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard · <?= h(SCHOOL) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="dashboard.css"></head><body>
<nav class="side" aria-label="Main">
  <div class="mark"><?= h(explode(' ', SCHOOL)[0]) ?></div>
  <a href="#"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg>Account</a>
  <a href="#" class="on" aria-current="page"><svg viewBox="0 0 24 24"><path d="M3 11l9-7 9 7v9h-6v-6H9v6H3z"/></svg>Dashboard</a>
  <a href="#"><svg viewBox="0 0 24 24"><path d="M5 4h11l3 3v13H5z M9 9h7 M9 13h7 M9 17h5"/></svg>Courses</a>
  <a href="#"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="15" rx="1"/><path d="M4 10h16M9 3v4M15 3v4"/></svg>Calendar</a>
  <a href="#"><svg viewBox="0 0 24 24"><path d="M3 6h18v12H3z M3 6l9 7 9-7"/></svg>Inbox<span class="badge">3</span></a>
  <a href="#"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.3-1 1-1 1.7M12 17v.5"/></svg>Help</a>
  <a href="index.php" class="out"><svg viewBox="0 0 24 24"><path d="M14 4h6v16h-6 M10 8l-4 4 4 4 M6 12h10"/></svg>Log Out</a>
</nav>
<div class="main">
  <header class="hello">
    <h1>Hello, <?= h($name) ?>!</h1>
    <p class="verified"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10"/></svg>Signed in with number matching · <?= h($r['device']) ?> · <?= h($r['time']) ?></p>
  </header>
  <div class="tabs" role="tablist"><span class="tab on">Dashboard</span><span class="tab">Courses</span></div>

  <div class="grid">
    <section class="panel">
      <h2>Course work</h2>
      <div class="counts">
        <div><b>5</b><span>Due</span></div>
        <div class="miss"><b>1</b><span>Missing</span></div>
        <div class="sub"><b>42</b><span>Submitted</span></div>
      </div>
      <ul class="work">
      <?php foreach ($work as [$c, $t, $d, $p]): ?>
        <li><span class="wico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6z M9 10h6 M9 14h6 M9 18h3"/></svg></span>
          <div><a href="#"><?= h($t) ?></a><small><?= h($c) ?> · <?= $p ?> pts</small></div>
          <time><?= h($d) ?></time></li>
      <?php endforeach; ?>
      </ul>
    </section>

    <section class="panel">
      <h2>Announcements</h2>
      <?php foreach ($news as [$t, $who, $c, $d, $body]): ?>
        <article class="news"><span class="av"><?= h($ini($who)) ?></span>
          <div><h3><?= h($t) ?></h3><small><?= h($who) ?> · <?= h($c) ?> · <?= h($d) ?></small><p><?= h($body) ?></p></div></article>
      <?php endforeach; ?>
    </section>

    <section class="panel">
      <h2>Course grades</h2>
      <div class="courses">
      <?php foreach ($courses as [$c, $g, $col]): ?>
        <a href="#" class="course"><span class="cover" style="--c:<?= $col ?>"><b><?= h($g) ?></b></span>
          <strong><?= h($c) ?></strong><small>First Semester SY 2026-2027</small></a>
      <?php endforeach; ?>
      </div>
    </section>

    <section class="panel">
      <h2>People</h2>
      <?php foreach ($people as [$n, $c]): ?>
        <div class="person"><span class="av"><?= h($ini($n)) ?></span><div><strong><?= h($n) ?></strong><small><?= h($c) ?></small><small>Teacher</small></div></div>
      <?php endforeach; ?>
    </section>
  </div>
  <p class="proto">Academic prototype (Usable Security, IT31S2 Group 2). All courses and names are examples.</p>
</div>
</body></html>

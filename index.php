<?php $pageTitle = 'Home'; include 'includes/header.php'; include 'includes/db_connect.php'; ?>
<br>
<h1 style="text-align: center; color: #FFD700;">Welcome to Event Watch</h1>
<p style="text-align: center; color: white;">Your go-to platform for discovering and tracking events around you.</p>
<br>
<div class="HomeCards1">
    <div class="card">
        <h2>Cultural Events</h2>
        <p style="margin-top:10px; text-align:center; font-size:1.13em;">
            Experience the vibrant traditions, music, and art from diverse cultures at our featured cultural events.
        </p>
    </div>
    <div class="card">
        <h2>Tech Events</h2>
        <p style="margin-top:10px; text-align:center; font-size:1.13em;">
            Be  aware of the current trends and innovations in technology by attending our tech events.
        </p>
    </div>
    <div class="card">
        <h2>Hackathons</h2>
        <p style="margin-top:10px; text-align:center; font-size:1.13em;">
            Collaborate, code, and compete in our hackathons—perfect for creative problem solvers and tech enthusiasts.
        </p>
    </div>
</div>
<div style="text-align: center; margin-top: 40px;">
    <a href="events/view_events.php">
        <button class="button-exploreevents">
            <svg class="svgIcon" viewBox="0 0 512 512" height="1em" xmlns="http://www.w3.org/2000/svg"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm50.7-186.9L162.4 380.6c-19.4 7.5-38.5-11.6-31-31l55.5-144.3c3.3-8.5 9.9-15.1 18.4-18.4l144.3-55.5c19.4-7.5 38.5 11.6 31 31L325.1 306.7c-3.2 8.5-9.9 15.1-18.4 18.4zM288 256a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"></path></svg>
            Explore
        </button>
    </a>
</div>
<?php include 'includes/footer.php'; ?>
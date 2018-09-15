<?php
	?>
	<h1>The {YOUR_COMPANY} Redirectererer&trade;</h1>
	<head><title>The {YOUR_COMPANY} Redirectererer&trade;</title></head>

	<?php
	// *******************
	// DATABASE CONNECTION
	// *******************
	// Makes connection with the database containing the redirect data.
	$connection = new mysqli("{YOUR_HOST_NAME}", "{YOUR_USERNAME}", "{YOUR_USER_PASSWORD}", "{YOUR_DATABASE_NAME}");

	// Dies with an error message if the connection to the database fails.
	if($connection->connect_error)
	{
		die("Connection to database failed: ".$connection->connect_error);
	}

	// Creates the 'redirect' table if it doesn't already exist, and inserts a new record for Google (just so that the table has at least one record).
	$result = $connection->query("SELECT ID FROM redirect");
	if(empty($result))
	{
		$connection->query("CREATE TABLE redirect(ID varchar(20) NOT NULL, URL varchar(255) NOT NULL, shortname varchar(255), PRIMARY KEY(ID), timestamp int(11) NOT NULL, IP varchar(45) NOT NULL, proxyIP varchar(45) NOT NULL)");
		$connection->query("INSERT INTO redirect (ID, URL, shortname, timestamp, IP, proxyIP) VALUES ('".substr(md5('http://google.com'), 0, 6)."', 'http://google.com', 'google', ".time().", '".$_SERVER['REMOTE_ADDR']."', '".$_SERVER['HTTP_X_FORWARDED_FOR']."')");
	}

	// ***********
	// REDIRECTION
	// ***********
	if(isset($_GET['r']))
	{
		// Gets the URL associated with either the shortname or ID.
		$url = $connection->query("SELECT URL FROM redirect WHERE `shortname`='".$_GET['r']."' OR `ID`='".$_GET['r']."' LIMIT 1");

		// Gets the URL to redirect to.
		$redirectURL = $url->fetch_object()->URL;

		// Adds the protocol if it is missing, defaulting to HTTP.
		if ((strpos($redirectURL, 'http://') === false) && (strpos($redirectURL, 'https://') === false))
		{
		    $redirectURL = 'http://'.$redirectURL;
		}

		// Redirects to the URL.
		header("Location: ".$redirectURL); // Script will end here if r is set.
	}
	
	// ********
	// ADDITION
	// ********
	if(isset($_GET['a'])) // Displays stuff for adding.
	{
		?>
		<form method="POST" action="/">
			<label for="URL">URL</label>
				<input type="text" name="URL" value="<?php echo $_GET['a']; ?>">
			<label for="shortname">Shortname (optional)</label>
				<input type="text" name="shortname">
			<input type="submit" name="submit">
		</form>
		<?php

		// YOU SHOULD PROBABLY ASK FOR THE MAGIC WORDS (you should probably password-protect the adding of new shortlinks).
	}
	elseif(isset($_POST['submit'])) // Displays stuff for submitting new addition.
	{
		// Makes a query to see if the URL already exists in the database.
		$urlQuery = $connection->query("SELECT ID, shortname FROM redirect WHERE `URL`='".$_POST['URL']."' LIMIT 1");

		// If a result is returned, then display an error telling the user this.
		if ($urlQuery->num_rows == 1)
		{
			// Stores the result from the earlier query.
			$result = $urlQuery->fetch_object();

			// Sets a base from where new shortlinks should be calculated.
			$baseURL = "{YOUR_BASE_URL}"; // For example, use something like "goo.gl" here.

			// Gives the redirection URL for the ID found.
			$urlID = $baseURL."?r=".$result->ID;

			// Displays a message to the user teling them that the ID exists with the following redirection URL.
			echo "The URL is already in the database at <a href='https://".$urlID."'>".$urlID."</a>.";

			// If the shortname exists, then we display the shortname redirection URL as well.
			if ($result->shortname != NULL)
			{
				$urlShortname = $baseURL."r=".$result->shortname;
				echo " Or try <a href='https://".$urlShortname."'>".$urlShortname."</a>.";
			}
		}
		else
		{
			// This section is run if the URL isn't already in the database.
			// We will now check to see if the ID exists.

			// Variable for controlling the loop.
			$trying = true;

			// This is our trial ID, which is the MD5 hash of the URL (to make it sufficiently random).
			$ID = substr(md5($_POST['URL']), 0, 6);
			$newID = $ID; // This is what our ID will ultimately be.

			// We loop while we have a conflict, and while we haven't tried more than 100 times.
			while ($trying and abs($newID - $ID) < 100)
			{
				$incrementQuery = $connection->query("SELECT ID FROM redirect WHERE `ID`='".$newID."' LIMIT 1");

				if ($incrementQuery->num_rows == 1)
				{
					$newID++;
				}
				else
				{
					$trying = false;
				}
			}

			if (abs($newID - $ID) != 100)
			{
				$connection->query("INSERT INTO redirect (ID, URL, shortname, timestamp, IP, proxyIP) VALUES ('".$newID."', '".$_POST['URL']."', '".$_POST['shortname']."', ".time().", '".$_SERVER['REMOTE_ADDR']."', '".$_SERVER['HTTP_X_FORWARDED_FOR']."')");

				if (true) // error
				{
					$getURL = $urlQuery = $connection->query("SELECT ID, shortname FROM redirect WHERE `URL`='".$_POST['URL']."' LIMIT 1");
					$result = $getURL->fetch_object();

					if ($result->shortname != NULL)
					{
						?>
						<p>Your shortlinks are <a href="<?php echo $baseURL."?r=".$result->shortname; ?>">r.blakey.family?r=<?php echo $result->shortname; ?></a>, or <a href=<?php echo $baseURL."?r=".$result->ID; ?>">r.blakey.family?r=<?php echo $result->ID; ?></a>.</p>
						<?php
					}
					else
					{
						?>
						<p> Your shortlink is <a href="<?php echo $baseURL."?r=".$result->shortname; ?>">r.blakey.family?r=<?php echo $result->shortname; ?></a>.</p>
						<?php
					}
				}
				else
				{
					// Some error message
				}				
			}
		}
		
		// $connection->query("INSERT INTO redirect (ID, URL, shortname, timestamp, IP, proxyIP) VALUES ('".substr(md5('http://google.com'), 0, 6)."', 'http://google.com', 'google', ".time().", ".$_SERVER['REMOTE_ADDR'].", ".$_SERVER['HTTP_X_FORWARDED_FOR'].")");
	}
	else
	{
		// Display funky page with link to addition.
		?>
		<h2><a href="http://redirect.blakey.family?a">Add a shortlink</a></h2>

		<?php
	}
?>
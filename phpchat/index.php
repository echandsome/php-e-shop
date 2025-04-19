<?php
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>Live Support</title>
		<link href="LiveSupportChat.css" rel="stylesheet" type="text/css">
		<style>
		* {
			box-sizing: border-box;
			font-family: -apple-system, BlinkMacSystemFont, "segoe ui", roboto, oxygen, ubuntu, cantarell, "fira sans", "droid sans", "helvetica neue", Arial, sans-serif;
			font-size: 16px;
		}
		body {
			background-color: #FFFFFF;
			margin: 0;
			padding: 15px;
		}
		</style>
	</head>
	<body>

		<!-- ADD THE BELOW CODE TO YOUR WEBSITE -->
        <script src="LiveSupportChat.js"></script>
        <script>
        new LiveSupportChat({
			// Leave blank to search all operators or specify a list of departments (General,Technical, etc)
			departments: '',
			// Automatically authenticate the visitor if they are logged in to the chat widget
			auto_login: true,
			// Notifications that will appear in the background when the chat widget is closed
			notifications: true,
			// The number of seconds to wait before checking for new messages (default: 5 seconds)
            update_interval: 5000,
			// Uncomment the below code to change the chat widget icon (<svg> or <img> tag)
			// icon: '',
			// Uncomment the below code to change the chat widget background color
			// background_color: '#000000',
        });
        </script>
		<!-- END -->

	</body>
</html>

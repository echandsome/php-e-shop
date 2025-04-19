<?php
// The database hostname. Usually it's localhost or the IP address of your server.
define('db_host','localhost');
// The database username.
define('db_user','root');
// The database password.
define('db_pass','oZk0972lL64icq8VC3f15JFv');
// The name of the database.
define('db_name','phpsupportchat_advanced');
// The database charset. Don't change this if in doubt.
define('db_charset','utf8');
// If enabled, the user will be prompted to enter a password to access the chat.
define('authentication_required',false);
// The maximum number of messages the user will see in the chat window.
define('max_messages',30);
// The full list of emojis seperated by comma.
define('emoji_list','1F600,1F601,1F602,1F603,1F604,1F605,1F606,1F607,1F608,1F609,1F60A,1F60B,1F60C,1F60D,1F60E,1F60F,1F610,1F611,1F612,1F613,1F614,1F615,1F616,1F617,1F618,1F619,1F61A,1F61B,1F61C,1F61D,1F61E,1F61F,1F620,1F621,1F622,1F623,1F624,1F625,1F626,1F627,1F628,1F629,1F62A,1F62B,1F62C,1F62D,1F62E,1F62F,1F630,1F631,1F632,1F633,1F634,1F635,1F636,1F637,1F641,1F642,1F643,1F644,1F910,1F911,1F912,1F913,1F914,1F915,1F920,1F921,1F922,1F923,1F924,1F925,1F927,1F928,1F929,1F92A,1F92B,1F92C,1F92D,1F92E,1F92F,1F9D0');
// The list of responses seperated by comma. They will appear when finding an agent to connect with.
define('automated_responses','Please wait...\nWe are trying to connect you with an agent...\nPlease hang tight...\nAny moment now...');
// The list of extended responses seperated by comma. They will appear after waiting too long.
define('extended_responses','We are sorry but all our agents are currently busy. Please try again later.');
/* Attachments */
// If enabled, the users will be able to upload files.
define('attachments_enabled',true);
// The directory where the files will be uploaded. Make sure you CHMOD this directory to the appropriate permissions.
define('file_upload_directory','attachments/');
// The maximum allowed file size in bytes (default 500KB).
define('max_allowed_upload_file_size',512000);
// The allowed file types seperated by comma.
define('file_types_allowed','.png,.jpg,.jpeg,.webp,.gif,.bmp');
/* Performance */
// The conversation refresh rate measured in miliseconds.
define('conversation_refresh_rate',5000);
// The requests refresh rate measured in miliseconds.
define('requests_refresh_rate',10000);
// The users online refresh rate measured in miliseconds.
define('users_online_refresh_rate',10000);
// The general info refresh rate measured in miliseconds.
define('general_info_refresh_rate',10000);
/* Mail */
// Send mail to the users, etc?
define('mail_enabled',true);
// This is the email address that will be used to send emails.
define('mail_from','noreply@example.com');
// The name of your business.
define('mail_name','Your Business Name');
// The email address where you want to receive the emails.
define('mail_to','johndoe@example.com');
// If enabled, the mail will be sent using SMTP.
define('SMTP',false);
// Your SMTP hostname.
define('smtp_host','smtp.example.com');
// Your SMTP port number.
define('smtp_port',465);
// Your SMTP username.
define('smtp_user','user@example.com');
// Your SMTP Password.
define('smtp_pass','');
?>

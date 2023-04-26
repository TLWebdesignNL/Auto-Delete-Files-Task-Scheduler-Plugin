# Auto Delete Files Task Scheduler Plugin
Joomla 4.1 Task Plugin to auto delete files older than a certain timeframe.

You're able set a folder from within your joomla root folder.
Then specify a timeframe in "minutes, hours or days".
It will then go over all the files in that folder only (not subfolders).

It will check the file for last time the file content was edited (PHP's filemtime function)
When it is older than the set time frame it wil automatically delete the file. 

The plugin also logs it's actions in the administrator/logs/joomla_scheduler.php by default.

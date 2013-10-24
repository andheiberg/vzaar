Vzaar API wrapper
===
A little cleanup and repackaging of the official Vzaar PHP wrapper.

[Documentation can be found here](http://developer.vzaar.com/docs/index.html)

[The original API wrapper can be found here](https://code.google.com/p/vzaar/)

#Install
Add ``` "andheiberg/vzaar": "dev-master" ``` to composer.json

For laravel add ``` 'Andheiberg\Vzaar\VzaarServiceProvider' ``` to app/config/app.php under providers and ``` 'Vzaar' => 'Andheiberg\Vzaar\Facades\Vzaar' ``` to app/config/app.php under aliases.
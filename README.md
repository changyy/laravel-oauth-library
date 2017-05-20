# laravel-oauth-library
An Simple Facebook/Google OAuth Client for Laravel Framework

# Laravel Usage

```
$ php composer.phar require changyy/laravel-oauth-library
$ php artisan make:controller OAuthConnect
$ vim app/Http/Controllers/OAuthConnect.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Connect extends \org\changyy\OAuth\Controller\Connect
{
    public function handleFacebookConnected($oauth_ret = []) {
        print_r($oauth_ret);
    }
    public function initFacebook() {
        if(!session_id())
            session_start();
        parent::initFacebook();
    }
    public function handleGoogleConnected($oauth_ret = []) {
        print_r($oauth_ret);
    }
}

$ vim routes/web.php
Route::get('/connect/facebook', 'OAuthConnect@connectFacebook');
Route::get('/connect/google', 'OAuthConnect@connectGoogle');

$ vim config/oauth.php
return [
    'methods' => [
        'facebook' => [
            'tag' => 'facebook',
            'default_graph_version' => 'v2.9',
            'app_id' => 'xxxxx',
            'secret_key' => 'xxxxxx',
            'scope' => [ 'email' ],
            'done_handler' => '/',
            'error_handler' => '/connect/error',
        ],  
        'google' => [
            'tag' => 'google',
            'client_id' => 'xxxxx',
            'client_secret' => 'xxxxx',
            'scope' => [ 'email', 'profile' ],
            'done_handler' => '/',
            'error_handler' => '/connect/error',
        ],
];
```

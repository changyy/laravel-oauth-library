<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AuthManager;

class OAuthConnect extends \org\changyy\OAuth\Controller\Connect
{
    public function handleFacebookConnected($oauth_ret = []) {
        if ($oauth_ret['status']) {
            $extra = [
                'name' => $oauth_ret['oauth_get_name'],
                'email' => $oauth_ret['oauth_get_email'],
                'social_email' => $oauth_ret['oauth_get_email'],
                'social_access_token' => $oauth_ret['oauth_access_token'],
                'social_link' => $oauth_ret['oauth_get_profile_url'],
                'remote_ip' => (string)$oauth_ret['others']['request']->ip(),
                'user_agent' => (string)$oauth_ret['others']['request']->header('User-Agent'),
            ];

            $user_ret = AuthManager::createOrUpdateAccount('facebook', $oauth_ret['oauth_get_uid'], $extra);
            if ($user_ret['status']) {
                $token_ret = AuthManager::createOrUpdateToken($user_ret['uid'], $extra['user_agent'], $extra);
                //print_r($token_ret);
                if ($token_ret['status']) {
                    return \Redirect::to($this->config_data['facebook']['done_handler'])->withCookie(cookie()->forever('token', $token_ret['token']));
                }
                return \Redirect::to($this->config_data['facebook']['error_handler'].'?'.http_build_query(array( 'type' => 'facebook', 'error' => 'service token create error')) );
            }
            return \Redirect::to($this->config_data['facebook']['error_handler'].'?'.http_build_query(array( 'type' => 'facebook', 'error' => 'service user create error')) );
        }
        return \Redirect::to($this->config_data['facebook']['error_handler'].'?'.http_build_query(array( 'type' => 'facebook', 'error' => 'oauth error')) );
    }

    public function initFacebook() {
        // use session
        if(!session_id())
            session_start();
        parent::initFacebook();
    }

    public function handleGoogleConnected($oauth_ret = []) {
        if ($oauth_ret['status']) {
            $extra = [
                'name' => $oauth_ret['oauth_get_name'],
                'email' => $oauth_ret['oauth_get_email'],
                'social_email' => $oauth_ret['oauth_get_email'],
                'social_access_token' => $oauth_ret['oauth_access_token'],
                'social_refresh_token' => $oauth_ret['oauth_refresh_token'],
                'social_link' => $oauth_ret['oauth_get_profile_url'],
                'remote_ip' => (string)$oauth_ret['others']['request']->ip(),
                'user_agent' => (string)$oauth_ret['others']['request']->header('User-Agent'),
            ];

            $user_ret = AuthManager::createOrUpdateAccount('google', $oauth_ret['oauth_get_uid'], $extra);
            if ($user_ret['status']) {
                $token_ret = AuthManager::createOrUpdateToken($user_ret['uid'], $extra['user_agent'], $extra);
                //print_r($token_ret);
                if ($token_ret['status']) {
                    return \Redirect::to($this->config_data['google']['done_handler'])->withCookie(cookie()->forever('token', $token_ret['token']));
                }
                return \Redirect::to($this->config_data['google']['error_handler'].'?'.http_build_query(array( 'type' => 'google', 'error' => 'service token create error')) );
            }
            return \Redirect::to($this->config_data['google']['error_handler'].'?'.http_build_query(array( 'type' => 'google', 'error' => 'service user create error')) );
        }
        return \Redirect::to($this->config_data['google']['error_handler'].'?'.http_build_query(array( 'type' => 'google', 'error' => 'oauth error')) );

    }
}

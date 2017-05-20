<?php
namespace org\changyy\OAuth\Controller;

use \Illuminate\Http\Request;
use \App\Http\Controllers\Controller;

class Connect extends Controller {
    public function __construct() {
        $this->config_data = [
            'facebook' => [
                'tag' => 'facebook',
                'graph_version' => \Config::get('oauth.methods.facebook.graph_version'),
                'app_id' => \Config::get('oauth.methods.facebook.app_id'),
                'app_secret' => \Config::get('oauth.methods.facebook.secret_key'),
                'scope' => \Config::get('oauth.methods.facebook.scope'),
                'query_me_fields' => \Config::get('oauth.methods.facebook.query_me_fields'),
                'error_handler' => \Config::get('oauth.methods.facebook.error_handler'),
                'done_handler' => \Config::get('oauth.methods.facebook.done_handler'),
            ],
            'google' => [
                'tag' => 'google',
                'client_id' => \Config::get('oauth.methods.google.client_id'),
                'client_secret' => \Config::get('oauth.methods.google.client_secret'),
                'scope' => \Config::get('oauth.methods.google.scope'),
                'error_handler' => \Config::get('oauth.methods.google.error_handler'),
                'done_handler' => \Config::get('oauth.methods.google.done_handler'),
            ]
        ];
        if (empty($this->config_data['facebook']['query_me_fields']))
            $this->config_data['facebook']['query_me_fields'] = 'email,name,link,id,cover';
        if (empty($this->config_data['facebook']['scope']))
            $this->config_data['facebook']['scope'] = ['email'];
        if (empty($this->config_data['facebook']['graph_version']))
            $this->config_data['facebook']['graph_version'] = 'v2.9';
        if (empty($this->config_data['google']['scope']))
            $this->config_data['google']['scope'] = ['email', 'profile'];
    }

    protected function initFacebook() {
        $this->fb = new \Facebook\Facebook([
            'app_id' => $this->config_data['facebook']['app_id'],
            'app_secret' => $this->config_data['facebook']['app_secret'],
            'default_graph_version' => $this->config_data['facebook']['graph_version'],
            //'default_access_token' => '{access-token}', // optional
        ]);
    }

    public function handleFacebookConnected($oauth_ret = []) {
        return \Redirect::to($this->config_data['facebook']['done_handler'].'?'.http_build_query($oauth_ret) );
    }

    public function disconnectFacebook(Request $request) {
        return get_class().'-'.__FUNCTION__;
    }

    public function connectFacebook(Request $request) {
        $this->initFacebook();

        $helper = $this->fb->getRedirectLoginHelper();
        $init_url = $helper->getLoginUrl(\Request::url(), $this->config_data['facebook']['scope']);

        $code = $request->input('code');
        $error = $request->input('error');

        $accessToken = NULL;
        if (!empty($code)) {
            try{
                $accessToken = $helper->getAccessToken();
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                $accessToken = NULL;
                //print_r($e);
            } catch (Exception $e) {
                $accessToken = NULL;
                //print_r($e);
            }

            if (empty($accessToken)) {
                //echo $init_url; return;
                return \Redirect::away($init_url);
            }
            // echo "accessToken: $accessToken\n";
            // echo "code: $code\n";

            $oauth_get_token = $accessToken;
            $oauth_get_uid = false;
            $oauth_get_name = false;
            $oauth_get_email = false;
            $oauth_get_profile_url = false;
            $oauth_get_profile_image = false;

            try {
                $response = $this->fb->get('/me?fields='.$this->config_data['facebook']['query_me_fields'], $accessToken);
                $user_profile = $response->getGraphNode();
                print_r($user_profile);
                $oauth_get_uid = $user_profile->getField('id');
                $oauth_get_name = $user_profile->getField('name');
                $oauth_get_email = $user_profile->getField('email');
                $oauth_get_profile_url = $user_profile->getField('link');
                if (empty($oauth_get_profile_url)) {
                        $oauth_get_profile_url = false;
                }

                if (empty($oauth_get_email)) {
                        $oauth_get_email = false;
                }   

                if (!empty($oauth_get_uid)) {
                        if ($oauth_get_profile_url === false) {
                                $oauth_get_profile_url = "https://www.facebook.com/" . $oauth_get_uid;
                        }   
                        if (!strstr($oauth_get_profile_url, 'app_scoped_user_id')) {
                                $oauth_get_profile_image = "https://graph.facebook.com/" . $oauth_get_uid . "/picture";
                        }
                }
                if (!empty($oauth_get_token) && $oauth_get_uid !== false && !empty($oauth_get_uid)) {
                    try {
                        $long_live_token = $this->fb->getOAuth2Client()->getLongLivedAccessToken($oauth_get_token);
                        if (!empty($long_live_token))
                                $oauth_get_token = (string)$long_live_token;
                    } catch (Exception $e) {
                    }
                }

            } catch (Exception $e) {

            }
            $output = [
                'status' => $oauth_get_uid !== false,
                'oauth_access_token' => $oauth_get_token,
                'oauth_get_token' => $oauth_get_token,
                'oauth_get_uid' => $oauth_get_uid,
                'oauth_get_name' => $oauth_get_name,
                'oauth_get_email' => $oauth_get_email,
                'oauth_get_profile_url' => $oauth_get_profile_url,
                'oauth_get_profile_image' => $oauth_get_profile_image,
                'others' => [
                    'request' => $request,
                    'graphNode' => $user_profile,
                ]
            ];
            return $this->handleFacebookConnected($output);
         } else {
            if (!empty($error)) {
                return \Redirect::to($this->config_data['facebook']['error_handler'].'?'.http_build_query(array( 'type' => $this->config_data['facebook']['tag'], 'error' => $error)) );
            } 
            // redirect to Facebook Oauth flow
            return \Redirect::away($init_url);
        }
        return get_class().'-'.__FUNCTION__;
    }

    public function handleGoogleConnected($oauth_ret = []) {
        return \Redirect::to($this->config_data['google']['done_handler'].'?'.http_build_query($oauth_ret) );
    }

    public function connectGoogle(Request $request)
    {
        $code = $request->input('code');
        $error = $request->input('error');

        $oauth_state = [];
        $init_url = 'https://accounts.google.com/o/oauth2/auth?'. http_build_query( array(
            'scope' => implode(' ', $this->config_data['google']['scope']),
            'response_type' => 'code', 
            'redirect_uri' => \Request::url(),
            'access_type' => 'offline',
            'approval_prompt' => 'force',
            'client_id' => $this->config_data['google']['client_id'],
            'state' => count($oauth_state) == 0 ? NULL : http_build_query($oauth_state)
        ));
        
        $accessToken = NULL;
        $oauth_get_token = false;
        $oauth_get_refresh_token = false;
        $oauth_get_uid = false;
        $oauth_get_name = false;
        $oauth_get_email = false;
        $oauth_get_profile_url = false;
        $oauth_get_profile_image = false;

        if (!empty($code)) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v3/token');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( array(
                    'client_id' => $this->config_data['google']['client_id'],
                    'client_secret' => $this->config_data['google']['client_secret'],
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => \Request::url(),
            )));
            $auth_ret = @json_decode(curl_exec($ch), true);
    
            //if (isset($auth_ret['error']) && $auth_ret['error'] == 'invalid_grant') {
            //    return \Redirect::to('/connect/error?'.http_build_query(array( 'type' => 'google', 'error' => 'invalid_grant')) );
            //}
            if (isset($auth_ret['access_token']) && isset($auth_ret['token_type']) && isset($auth_ret['refresh_token'])) {
                $accessToken = $auth_ret['access_token'];
                $oauth_get_token = $accessToken;
                $oauth_get_refresh_token = $auth_ret['refresh_token'];
            }

            if (empty($accessToken)) {
                //echo $init_url; return;
                return \Redirect::away($init_url);
            }

            $profile_ret = @json_decode(file_get_contents('https://www.googleapis.com/plus/v1/people/me?'.http_build_query( array(
                'access_token' => $accessToken,
            ))), true);
            //print_r($profile_ret);
            if (isset($profile_ret['emails']) && is_array($profile_ret['emails'])) {
                if (count($profile_ret['emails']) > 0 && isset($profile_ret['emails'][0]['value']) && !empty($profile_ret['emails'][0]['value']) )
                    $oauth_get_email = $profile_ret['emails'][0]['value'];
            }
            if (isset($profile_ret['displayName']))
                $oauth_get_name = $profile_ret['displayName'];
            if (isset($profile_ret['id']))
                $oauth_get_uid = $profile_ret['id'];
            if (isset($profile_ret['url']))
                $oauth_get_profile_url = $profile_ret['url'];
            if (isset($profile_ret['image']) && is_array($profile_ret['image']) && isset($profile_ret['image']['url']))
                $oauth_get_profile_image = $profile_ret['image']['url'];

            //echo "accessToken:[$accessToken]\n";
            //echo "oauth_get_token:[$oauth_get_token]\n"; 
            //echo "oauth_get_refresh_token:[$oauth_get_refresh_token]\n"; 
            //echo "oauth_get_uid:[$oauth_get_uid]\n";
            //echo "oauth_get_name:[$oauth_get_name]\n";
            //echo "oauth_get_email:[$oauth_get_email]\n";
            //echo "oauth_get_profile_url:[$oauth_get_profile_url]\n";
            //echo "oauth_get_profile_image:[$oauth_get_profile_image]\n";
            $output = [
                'status' => $oauth_get_uid !== false,
                'oauth_access_token' => $oauth_get_token,
                'oauth_refresh_token' => $oauth_get_refresh_token,
                'oauth_get_token' => $oauth_get_token,
                'oauth_get_uid' => $oauth_get_uid,
                'oauth_get_name' => $oauth_get_name,
                'oauth_get_email' => $oauth_get_email,
                'oauth_get_profile_url' => $oauth_get_profile_url,
                'oauth_get_profile_image' => $oauth_get_profile_image,
                'others' => [
                    'request' => $request,
                    'profile' => $profile_ret,
                ]
            ];
            return $this->handleGoogleConnected($output);
         } else {
            if (!empty($error)) {
                return \Redirect::to($this->config_data['google']['error_handler'].'?'.http_build_query(array( 'type' => $this->config_data['google']['tag'], 'error' => $error)) );
            } 
            // redirect to Facebook Oauth flow
            return \Redirect::away($init_url);
        }
        return get_class().'-'.__FUNCTION__;
    }
}

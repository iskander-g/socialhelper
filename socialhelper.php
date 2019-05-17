<?php

class SocialHelper
{
    // Facebook
    // returns JSON object like {'og_object' : {info}, 'shares': {comment_count: int, share_count: int}, 'id': url}
    const FB_SHARES_COUNT_URL = 'https://graph.facebook.com/%1$s';
    const FB_SHARE_URL = 'https://www.facebook.com/sharer.php?u=%1$s';

    // VK.com
    // returns JSONP string like VK.Share.count(1234, 1664);. First argument - index, second - shares count
    const VK_SHARES_COUNT_URL = 'https://vk.com/share.php?act=count&index=1234&url=%1$s';
    const VK_REGEXP = '/VK\.Share\.count\([0-9]+, ([0-9]+)\)\;/is';
    const VK_SHARE_URL = 'http://vk.com/share.php?url=%1$s&noparse=%2$s&title=%3$s&image=%4$s';
    const VK_LIGHT_SHARE_URL = 'http://vk.com/share.php?url=%1$s';

    // OK.ru
    // returns JSONP string like ODKL.updateCount('pageshares','18');
    const OK_SHARES_COUNT_URL = 'https://connect.ok.ru/dk?st.cmd=extLike&uid=pageshares&callback=?&ref=%1$s';
    const OK_REGEXP = '/ODKL\.updateCount\(\'pageshares\',\'([0-9]+)\'\)\;/is';
    const OK_SHARE_URL = 'https://connect.ok.ru/offer?url=%1$s';

    // Moy mir (my.mail.ru)
    // returns JSONP string like pageshares({"http:\/\/www.beboss.ru\/rating":{"shares":0,"clicks":0}});
    const MR_SHARES_COUNT_URL = 'https://connect.mail.ru/share_count?callback=1&func=pageshares&url_list=%1$s';
    const MR_REGEXP = '/pageshares\((.*)\);/is';
    const MR_SHARE_URL = 'http://connect.mail.ru/share?url=%1$s&title=%2$s&description=%3$s&image_url=%4$s';

    const TW_SHARE_URL = 'https://twitter.com/share?url=%1$s&text=%2$s&via=%3$s&hashtags=%4$s';

    const POCKET_SHARE_URL = 'https://getpocket.com/save?url=%1$s';

    static $workingProxy;

    /**
     * Creates a sharing URL for Twitter
     *
     * @param string $url URL you want to share
     * @param string $text Tweet's text
     * @param string $via Name of your main twitter account you want to promote
     * @param string $hashtags List of hashtags, comma separated
     *
     * @return string
     */
    public static function getTwitterShareUrl($url, $text, $via, $hashtags)
    {
        return sprintf(self::TW_SHARE_URL, urlencode($url), $text, $via, $hashtags);
    }

    /**
     * Creates a sharing URL for Facebook
     *
     * @param string $url URL you want to share
     *
     * @return string
     */
    public static function getFacebookShareUrl($url)
    {
        return sprintf(self::FB_SHARE_URL, urlencode($url));
    }

    /**
     * Gets an amount of shares for URL on Facebook
     *
     * @param string $url URL shares amount of which you wnat to get
     *
     * @return integer
     */
    public static function getFacebookSharesCount($url)
    {
        $fbUrl = sprintf(self::FB_SHARES_COUNT_URL, urlencode($url));
        $data = file_get_contents($fbUrl);

        if (!$data) {
            $i = 0;
            $headers = array("X-Requested-With: XMLHttpRequest",
                "User-Agent:Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1",
                "Referer:$url");

            while (!$data && $i < 15) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fbUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($ch, CURLOPT_PROXY, self::$workingProxy);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                $data = curl_exec($ch);
                curl_close($ch);

                if (strpos($data, "Application request limit reached")) {
                    $data = false;
                }

                if (!$data) {
                    $i++;
                    self::$workingProxy = ProxyServer::getWorkingProxy();
                }
            }
        }
        try {
            $data = json_decode($data);

            if ($data->id == $url) {
                return $data->share->share_count;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Creates a sharing URL for Vk.com
     *
     * @param string $url URL you want to share
     * @param string $noparse Send true or 'true' if you want to send custom title, desc and image
     * @param string $title Title of the post
     * @param string $desc Description of the URL
     * @param string $image URL of an image you want to add to post
     *
     * @return string
     */
    public static function getVkShareUrl($url, $noparse = 'false', $title = null , $desc = null, $image = null)
    {
        if ($noparse === true || $noparse == 'true') {
            $noparse = 'true';
            return sprintf(self::VK_SHARE_URL, urlencode($url), $noparse, $title, $image);
        } else {
            return sprintf(self::VK_LIGHT_SHARE_URL, urlencode($url));
        }
    }

    /**
     * @param string $url
     * @return string
     */
    public static function getPocketShareUrl($url)
    {
        return sprintf(self::POCKET_SHARE_URL, urlencode($url));
    }

    /**
     * Gets an amount of shares for URL on Vk.com
     *
     * @param string $url URL shares amount of which you wnat to get
     *
     * @return integer
     */
    public static function getVkSharesCount($url)
    {
        $data = file_get_contents(sprintf(self::VK_SHARES_COUNT_URL, urlencode($url)));

        if (preg_match(self::VK_REGEXP, $data, $match)) {
            $data = intval($match[1]);
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Creates a sharing URL for Odnoklassniki (ok.ru)
     *
     * @param string $url URL you want to share
     *
     * @return string
     */
    public static function getOkShareUrl($url)
    {
        return sprintf(self::OK_SHARE_URL, urlencode($url));
    }

    /**
     * Gets an amount of shares for URL on OK.ru
     *
     * @param string $url URL shares amount of which you wnat to get
     *
     * @return integer
     */
    public static function getOkSharesCount($url)
    {
        $data = file_get_contents(sprintf(self::OK_SHARES_COUNT_URL, urlencode($url)));

        if (preg_match(self::OK_REGEXP, $data, $match)) {
            $data = intval($match[1]);
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Creates a sharing URL for Moy mir (my.mail.ru)
     *
     * @param string $url URL you want to share
     * @param string $title Title of the post
     * @param string $desc Description of the URL
     * @param string $image URL of an image you want to add to post
     *
     * @return string
     */
    public static function getMoymirShareUrl($url, $title, $desc, $image)
    {
        return sprintf(self::MR_SHARE_URL, urlencode($url), $title, $desc, $image);
    }

    /**
     * Gets an amount of shares for URL on Moy mir (my.mail.ru)
     *
     * @param string $url URL shares amount of which you wnat to get
     *
     * @return integer
     */
    public static function getMoymirSharesCount($url)
    {
        $data = file_get_contents(sprintf(self::MR_SHARES_COUNT_URL, urlencode($url)));

        if (preg_match(self::MR_REGEXP, $data, $match)) {
            $data = $match[1];
            try {
                $data = json_decode($data);

                if (isset($data->$url->shares)) {
                    return $data->$url->shares;
                } else {
                    return false;
                }
            } catch (Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }
}

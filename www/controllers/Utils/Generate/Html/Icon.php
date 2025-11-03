<?php

namespace Controllers\Utils\Generate\Html;

class Icon
{
    /**
     *  Generate OS icon image
     */
    public static function os(string $os) : string
    {
        if (preg_match('/centos/i', $os)) {
            return '<img src="/assets/icons/products/centos.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/rocky/i', $os)) {
            return '<img src="/assets/icons/products/rockylinux.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/alma/i', $os)) {
            return '<img src="/assets/icons/products/almalinux.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/oracle/i', $os)) {
            return '<img src="/assets/icons/products/oracle.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/fedora/i', $os)) {
            return '<img src="/assets/icons/products/fedora.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/redhat/i', $os)) {
            return '<img src="/assets/icons/products/redhat.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/debian|armbian/i', $os)) {
            return '<img src="/assets/icons/products/debian.png" class="icon-np" title="' . $os . '" />';
        } elseif (preg_match('/ubuntu|kubuntu|xubuntu|mint/i', $os)) {
            return '<img src="/assets/icons/products/ubuntu.png" class="icon-np" title="' . $os . '" />';
        }

        // Return generic icon
        return '<img src="/assets/icons/products/tux.png" class="icon-np" title="' . $os . '" />';
    }

    /**
     *  Generate product icon image
     */
    public static function product(string $product) : string
    {
        if (preg_match('/python/i', $product)) {
            return '<img src="/assets/icons/products/python.png" class="icon-np" />';
        } elseif (preg_match('/^code$/i', $product)) {
            return '<img src="/assets/icons/products/vscode.png" class="icon-np" />';
        } elseif (preg_match('/^firefox/i', $product)) {
            return '<img src="/assets/icons/products/firefox.png" class="icon-np" />';
        } elseif (preg_match('/^chrome-$/i', $product)) {
            return '<img src="/assets/icons/products/chrome.png" class="icon-np" />';
        } elseif (preg_match('/^chromium-$/i', $product)) {
            return '<img src="/assets/icons/products/chromium.png" class="icon-np" />';
        } elseif (preg_match('/^brave-browser$/i', $product)) {
            return '<img src="/assets/icons/products/brave.png" class="icon-np" />';
        } elseif (preg_match('/^filezilla/i', $product)) {
            return '<img src="/assets/icons/products/filezilla.png" class="icon-np" />';
        } elseif (preg_match('/^java/i', $product)) {
            return '<img src="/assets/icons/products/java.png" class="icon-np" />';
        } elseif (preg_match('/^teams$/i', $product)) {
            return '<img src="/assets/icons/products/teams.png" class="icon-np" />';
        } elseif (preg_match('/^teamviewer$/i', $product)) {
            return '<img src="/assets/icons/products/teamviewer.png" class="icon-np" />';
        } elseif (preg_match('/^thunderbird/i', $product)) {
            return '<img src="/assets/icons/products/thunderbird.png" class="icon-np" />';
        } elseif (preg_match('/^vlc/i', $product)) {
            return '<img src="/assets/icons/products/vlc.png" class="icon-np" />';
        }

        // Return generic icon
        return '<img src="/assets/icons/package.svg" class="icon-np" />';
    }
}

<?php

namespace Luminous\Bridge\Post\Entities;

use Luminous\Bridge\Post\Entity as BaseEntity;

class AttachmentEntity extends BaseEntity
{
    /**
     * The real URL of upload directory.
     *
     * @var string
     */
    protected static $attachmentUrlReal;

    /**
     * Get the attachment relative path from original URI.
     *
     * @uses \wp_upload_dir()
     *
     * @param string $uri
     * @return string
     */
    public static function attachmentPath($uri)
    {
        if (is_null(static::$attachmentUrlReal)) {
            static::$attachmentUrlReal = wp_upload_dir()['baseurl'];
        }

        return str_replace(static::$attachmentUrlReal, 'uploads', $uri);
    }

    /**
     * Get the caption.
     *
     * @return string
     */
    protected function getCaptionAttribute()
    {
        return $this->excerpt(0, false);
    }

    /**
     * Get the alt.
     *
     * @return string
     */
    protected function getAltAttribute()
    {
        return $this->meta('_wp_attachment_image_alt');
    }

    /**
     * Get the full path of the file.
     *
     * @uses \get_attached_file()
     *
     * @return string
     */
    protected function getFilePathAttribute()
    {
        return get_attached_file($this->id);
    }

    /**
     * Get the MIME type.
     *
     * @return string
     */
    protected function getMimeTypeAttribute()
    {
        return $this->original->post_mime_type;
    }

    /**
     * Determine if the file is a specific type.
     *
     * @param string $type
     * @return bool
     */
    public function is($type)
    {
        $actual = $this->mime_type;

        if ($actual === $type) {
            return true;
        } elseif (strpos($type, '/') === false) {
            return strpos($actual, $type.'/') === 0;
        }

        return false;
    }

    /**
     * Get the file URL.
     *
     * @return string
     */
    protected function getSrcAttribute()
    {
        return $this->src();
    }

    /**
     * Get the relative path of the file.
     *
     * @uses \wp_attachment_is_image()
     * @uses \image_downsize()
     * @uses \wp_get_attachment_url()
     *
     * @param string|null $size
     * @return string
     */
    public function src($size = null)
    {
        if (wp_attachment_is_image($this->id)) {
            list($url) = image_downsize($this->id, $size);
        } else {
            $url = wp_get_attachment_url($this->id);
        }

        return static::attachmentPath($url);
    }
}

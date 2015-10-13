<?php

namespace Luminous\Bridge\Post\Entities;

use Luminous\Bridge\Post\Entity as BaseEntity;

class AttachmentEntity extends BaseEntity
{
    /**
     * The base URL of upload directory.
     *
     * @var string
     */
    protected static $attachmentBaseUrl;

    /**
     * Modify the attachment URL.
     *
     * @uses \wp_upload_dir()
     *
     * @param string $uri
     * @return string
     */
    public static function attachmentUrl($uri)
    {
        if (is_null(static::$attachmentBaseUrl)) {
            static::$attachmentBaseUrl = wp_upload_dir()['baseurl'];
        }

        return str_replace(static::$attachmentBaseUrl, 'uploads', $uri);
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
     * Get the File path.
     *
     * @uses \get_attached_file()
     *
     * @return string
     */
    protected function getFilePathAttribute()
    {
        return get_attached_file($this->original->ID);
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
        list($mimeType) = explode('/', $this->mime_type);
        return $mimeType === $type;
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
     * Get the file URL.
     *
     * @uses \wp_attachment_is_image()
     * @uses \image_downsize()
     * @uses \wp_get_attachment_url()
     *
     * @param string|null $size
     * @return string Relative URL.
     */
    public function src($size = null)
    {
        if (wp_attachment_is_image($this->original->ID)) {
            list($url) = image_downsize($this->original->ID, $size);
        } else {
            $url = wp_get_attachment_url($this->original->ID);
        }

        return static::attachmentUrl($url);
    }
}

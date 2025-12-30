<?php

declare(strict_types=1);

namespace Drupal\article_youtube\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'youtube_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "youtube_embed",
 *   label = @Translation("YouTube embed"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class YouTubeEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'width' => 560,
      'height' => 315,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Chiều rộng iframe'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 200,
      '#required' => TRUE,
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Chiều cao iframe'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 150,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    $summary[] = $this->t('Kích thước: @width x @height', [
      '@width' => $this->getSetting('width'),
      '@height' => $this->getSetting('height'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];

    foreach ($items as $delta => $item) {
      $url = $item->getUrl();
      if (!$url instanceof Url) {
        continue;
      }

      $video_id = $this->extractVideoId($url->toString());
      if (!$video_id) {
        $elements[$delta] = $this->buildFallbackLink($item);
        continue;
      }

      $embed_url = sprintf('https://www.youtube.com/embed/%s', $video_id);
      $elements[$delta] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['article-youtube-embed']],
        'iframe' => [
          '#type' => 'html_tag',
          '#tag' => 'iframe',
          '#attributes' => [
            'width' => $this->getSetting('width'),
            'height' => $this->getSetting('height'),
            'src' => $embed_url,
            'frameborder' => '0',
            'allow' => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
            'allowfullscreen' => 'allowfullscreen',
            'loading' => 'lazy',
            'title' => $this->t('YouTube video'),
          ],
        ],
      ];
    }

    return $elements;
  }

  /**
   * Trả về render array link nếu không lấy được video ID.
   */
  protected function buildFallbackLink(LinkItemInterface $item): array {
    $url = $item->getUrl();
    $link = Link::fromTextAndUrl($url->toString(), $url);

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['article-youtube-link']],
      'link' => $link->toRenderable(),
    ];
  }

  /**
   * Lấy YouTube video ID từ URL phổ biến (watch, share, embed, shorts).
   */
  protected function extractVideoId(string $url): ?string {
    $parts = parse_url($url);
    if (empty($parts['host'])) {
      return NULL;
    }

    $host = strtolower($parts['host']);
    $path = trim($parts['path'] ?? '', '/');
    $query = [];
    if (!empty($parts['query'])) {
      parse_str($parts['query'], $query);
    }

    $id = NULL;
    if (str_contains($host, 'youtu.be')) {
      $id = $path;
    }
    elseif (str_contains($host, 'youtube.com')) {
      if (!empty($query['v'])) {
        $id = $query['v'];
      }
      elseif (str_starts_with($path, 'embed/')) {
        $id = substr($path, strlen('embed/'));
      }
      elseif (str_starts_with($path, 'shorts/')) {
        $id = substr($path, strlen('shorts/'));
      }
    }

    if (!$id) {
      return NULL;
    }

    // Chỉ giữ ký tự hợp lệ cho video ID.
    if (preg_match('/^[\\w-]{4,}$/', $id)) {
      return $id;
    }

    return NULL;
  }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'user_id', 'region_id', 'provider', 'technology', 'issue_type', 'download_mbps', 'upload_mbps',
        'latency_ms', 'description', 'latitude', 'longitude', 'photo_path', 'severity_score', 'severity_level',
        'status', 'source', 'verified_by', 'verified_at', 'review_note', 'infrastructure_project_id', 'resolved_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'download_mbps' => 'float',
            'upload_mbps' => 'float',
            'latency_ms' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'severity_score' => 'integer',
            'verified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(InfrastructureProject::class, 'infrastructure_project_id');
    }

    /** Laporan yang dianggap "aktif" dalam perhitungan kesenjangan. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'verified');
    }

    /** Filter dinamis dari query string halaman daftar laporan. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, $term) {
                $q->where(function (Builder $w) use ($term) {
                    $w->where('code', 'like', "%{$term}%")
                        ->orWhere('provider', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('region', fn (Builder $r) => $r->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['issue_type'] ?? null, fn (Builder $q, $v) => $q->where('issue_type', $v))
            ->when($filters['severity'] ?? null, fn (Builder $q, $v) => $q->where('severity_level', $v))
            ->when($filters['technology'] ?? null, fn (Builder $q, $v) => $q->where('technology', $v))
            ->when($filters['country'] ?? null, fn (Builder $q, $v) => $q->whereHas('region', fn (Builder $r) => $r->where('country_id', $v)))
            ->when($filters['from'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v));
    }

    public function isEditable(): bool
    {
        return $this->status === 'pending';
    }

    public function statusLabel(): string
    {
        return config('signal.report_statuses.'.$this->status, $this->status);
    }

    public function issueLabel(): string
    {
        return config('signal.issue_types.'.$this->issue_type, $this->issue_type);
    }

    public function technologyLabel(): string
    {
        return config('signal.technologies.'.$this->technology, $this->technology);
    }

    public function severityLabel(): string
    {
        return config('signal.severity_levels.'.$this->severity_level, $this->severity_level);
    }

    /** Ekstrak teks murni tanpa tag HTML (untuk tabel ringkasan, validasi panjang, & ekspor CSV). */
    public static function toPlainText(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }
        $spaced = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6]|\/blockquote|\/pre)\b[^>]*>/i', ' ', $html);
        $decoded = html_entity_decode(strip_tags((string) $spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $decoded));
    }

    public function plainDescription(): string
    {
        return self::toPlainText($this->description);
    }

    /** Sanitasi HTML dari WYSIWYG editor dengan whitelist tag & atribut aman (anti-XSS). */
    public static function sanitizeHtml(?string $input): string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return '';
        }

        // Jika teks biasa tanpa tag HTML, ubah baris baru menjadi paragraf aman
        if ($input === strip_tags($input)) {
            return '<p>'.nl2br(e($input), false).'</p>';
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'blockquote', 'h3', 'h4', 'code', 'pre', 'a'];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="__root">'.$input.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementById('__root');
        if (! $root) {
            return '<p>'.e(self::toPlainText($input)).'</p>';
        }

        $cleanNode = function (\DOMNode $node) use (&$cleanNode, $allowedTags): void {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof \DOMComment) {
                    $node->removeChild($child);

                    continue;
                }

                if ($child instanceof \DOMElement) {
                    $tag = strtolower($child->tagName);

                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form'], true)) {
                        $node->removeChild($child);

                        continue;
                    }

                    $cleanNode($child);

                    if (! in_array($tag, $allowedTags, true)) {
                        // Pindahkan isi anak ke induknya (unwrap tag tidak dikenal seperti div/span)
                        while ($child->firstChild) {
                            $node->insertBefore($child->firstChild, $child);
                        }
                        $node->removeChild($child);

                        continue;
                    }

                    // Bersihkan semua atribut kecuali href yang valid pada <a>
                    $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
                    while ($child->attributes->length > 0) {
                        $child->removeAttributeNode($child->attributes->item(0));
                    }

                    if ($tag === 'a') {
                        if (preg_match('/^https?:\/\/[^\s<>"]+$/i', $href)) {
                            $child->setAttribute('href', $href);
                            $child->setAttribute('target', '_blank');
                            $child->setAttribute('rel', 'noopener noreferrer');
                        } else {
                            while ($child->firstChild) {
                                $node->insertBefore($child->firstChild, $child);
                            }
                            $node->removeChild($child);
                        }
                    }
                }
            }
        };

        $cleanNode($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    /** HTML terformat yang sudah disanitasi untuk ditampilkan pada detail laporan. */
    public function safeDescriptionHtml(): string
    {
        return self::sanitizeHtml($this->description);
    }
}

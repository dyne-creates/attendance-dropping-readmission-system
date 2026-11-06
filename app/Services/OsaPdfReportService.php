<?php

namespace Mpdf {
    if (! \function_exists('Mpdf\is_writable')) {
        /**
         * Compatibility polyfill for PHP's is_writable() on Windows environments
         * where directory token inspection fails despite actual write permissions.
         */
        function is_writable(string $path): bool
        {
            if (\is_writable($path)) {
                return true;
            }

            if (is_dir($path)) {
                $test = rtrim($path, '/\\').DIRECTORY_SEPARATOR.'.is_writable_test_'.uniqid('', true);
                $fp = @fopen($test, 'w');
                if ($fp !== false) {
                    fclose($fp);
                    @unlink($test);

                    return true;
                }

                return false;
            }

            $fp = @fopen($path, 'a');
            if ($fp !== false) {
                fclose($fp);

                return true;
            }

            return false;
        }
    }
}

namespace App\Services {

    use Mpdf\Mpdf;
    use Mpdf\Output\Destination;
    use RuntimeException;

    class OsaPdfReportService
    {
        /**
         * Generate institutional PDF report.
         *
         * @param  array<string, mixed>  $data
         */
        public function generate(array $data): string
        {
            $tempDir = storage_path('app/mpdf-temp');

            if (! is_dir($tempDir)) {
                if (! @mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
                    throw new RuntimeException(
                        'Unable to create mPDF temporary directory: '.$tempDir
                    );
                }
            }

            // Verify that PHP can write to the directory.
            if (! is_writable($tempDir)) {
                throw new RuntimeException(
                    'mPDF temporary directory is not writable: '.$tempDir
                );
            }

            // Process any base64 chart images into temp png files for crisp rendering in mPDF
            $tempImageFiles = [];
            $chartKeys = ['chart_drops', 'chart_excused', 'chart_timeline', 'chart_status', 'chart_absence'];
            $chartPaths = [];

            foreach ($chartKeys as $key) {
                $base64 = $data['charts'][$key] ?? null;
                if ($base64 && is_string($base64) && str_starts_with($base64, 'data:image')) {
                    $parts = explode(',', $base64, 2);
                    if (isset($parts[1])) {
                        $decoded = base64_decode($parts[1]);
                        if ($decoded !== false) {
                            $tempFile = $tempDir.DIRECTORY_SEPARATOR.uniqid('chart_', true).'.png';
                            file_put_contents($tempFile, $decoded);
                            $tempImageFiles[] = $tempFile;
                            $chartPaths[$key] = $tempFile;
                        }
                    }
                }
            }

            $data['chart_paths'] = $chartPaths;
            $data['logo_path'] = public_path('ub-logo-light.png');

            try {
                $mpdf = new Mpdf([
                    'tempDir' => $tempDir,
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 12,
                    'margin_right' => 12,
                    'margin_top' => 14,
                    'margin_bottom' => 18,
                    'margin_header' => 6,
                    'margin_footer' => 8,
                    'default_font' => 'sans-serif',
                ]);

                $mpdf->SetTitle('OSA Attendance, Dropping and Readmission Report');
                $mpdf->SetAuthor('University of Baguio - Office of Student Affairs');
                $mpdf->SetCreator('ADRS System');

                // Footer with page numbering
                $mpdf->SetHTMLFooter('
                    <table width="100%" style="border-top: 1px solid #d1d5db; font-size: 8pt; color: #6b7280; font-family: sans-serif; padding-top: 4px;">
                        <tr>
                            <td width="50%" align="left">University of Baguio &bull; Office of Student Affairs</td>
                            <td width="50%" align="right">Page {PAGENO} of {nbpg} &bull; Confidential</td>
                        </tr>
                    </table>
                ');

                $html = view('osa-staff.reports-pdf', $data)->render();
                $mpdf->WriteHTML($html);

                return $mpdf->Output('', Destination::STRING_RETURN);
            } catch (\Throwable $exception) {
                throw new RuntimeException('Failed to generate PDF report: '.$exception->getMessage(), 0, $exception);
            } finally {
                // Clean up temporary chart images
                foreach ($tempImageFiles as $file) {
                    if (file_exists($file)) {
                        @unlink($file);
                    }
                }
            }
        }
    }
}

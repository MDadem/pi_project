<?php

namespace App\Service;

use App\Entity\EventRegistration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Twig\Environment;
use Symfony\Component\Filesystem\Filesystem;

class TicketService
{
    private $projectDir;
    
    public function __construct(
        private readonly Environment $twig,
        string $projectDir
    ) {
        $this->projectDir = $projectDir;
    }

    public function generateQrCode(EventRegistration $registration): string
    {
        try {
            // Create QR code
            $qrCode = QrCode::create(json_encode([
                'ticketNumber' => $registration->getTicketNumber(),
                'eventId' => $registration->getEvent()->getId(),
                'userId' => $registration->getUser()->getId()
            ]));

            $qrCode->setSize(300);
            
            // Create writer and write QR code
            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            // Save QR code
            $path = $this->projectDir . '/public/uploads/qrcodes/';
            $filesystem = new Filesystem();
            
            if (!$filesystem->exists($path)) {
                $filesystem->mkdir($path, 0777);
            }

            $filename = $registration->getTicketNumber() . '.png';
            $result->saveToFile($path . $filename);

            return 'uploads/qrcodes/' . $filename;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to generate QR code: ' . $e->getMessage());
        }
    }

    public function generateTicketPdf(EventRegistration $registration): string
    {
        try {
            // Configure Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('chroot', $this->projectDir . '/public');

            $dompdf = new Dompdf($options);

            // Generate HTML content using Twig template
            $html = $this->twig->render('frontend/event/ticket.html.twig', [
                'registration' => $registration,
                'event' => $registration->getEvent(),
                'user' => $registration->getUser(),
                'qrCodeUrl' => $this->projectDir . '/public/' . $registration->getQrCode()
            ]);

            // Load HTML to Dompdf
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Save PDF
            $pdfPath = $this->projectDir . '/public/uploads/tickets/';
            $filesystem = new Filesystem();
            
            if (!$filesystem->exists($pdfPath)) {
                $filesystem->mkdir($pdfPath, 0777);
            }

            $pdfFilename = $registration->getTicketNumber() . '.pdf';
            file_put_contents($pdfPath . $pdfFilename, $dompdf->output());

            return 'uploads/tickets/' . $pdfFilename;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to generate PDF ticket: ' . $e->getMessage());
        }
    }
}

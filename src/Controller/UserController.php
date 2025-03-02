<?php

namespace App\Controller;


use App\Entity\User;
use App\Form\UserType;
use App\Service\AuthenticatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TCPDF;
use Twig\Environment;


// related to the reset pwd
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;






final class UserController extends AbstractController
{

    #[Route('/dashboard/edit-profile/{id}', name: 'app_edit_profile')]
    public function edit(User $user, Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->getUser() !== $user) {
            return $this->redirectToRoute('dashboard_signin');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('profileIMG')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('profile_images_directory'),
                        $newFilename
                    );
                    $user->setProfileIMG('uploads/profile_images/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_edit_profile', ['id' => $user->getId()]);
        }

        return $this->render('backend/user/edit_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/home/edit-profile/{id}', name: 'app_edit_profile_user')]
    public function edit_student(User $user, Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($this->getUser() !== $user) {
            return $this->redirectToRoute('dashboard');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('profileIMG')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('profile_images_directory'),
                        $newFilename
                    );
                    $user->setProfileIMG('uploads/profile_images/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_edit_profile_user', ['id' => $user->getId()]);
        }

        return $this->render('frontend/user/edit_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/dashboard/users', name: 'app_dashboard_users')]
    public function listUsers(EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $query = $entityManager->createQuery(
            'SELECT u FROM App\Entity\User u ORDER BY u.id ASC'
        )->setFirstResult($offset)->setMaxResults($limit);

        $users = new Paginator($query, true);
        $totalUsers = count($users);
        $totalPages = ceil($totalUsers / $limit);

        return $this->render('backend/user/users.html.twig', [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/dashboard/users/export', name: 'app_export_users_excel')]
    public function exportUsersExcel(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Fetch all users from the database
        $users = $entityManager->getRepository(User::class)->findAll();

        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers with styles
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'First Name');
        $sheet->setCellValue('C1', 'Last Name');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'Roles');
        $sheet->setCellValue('F1', 'Status');

        // Apply styles to headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Apply header styles to the first row
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Populate the spreadsheet with user data
        $row = 2;
        foreach ($users as $user) {
            $sheet->setCellValue('A' . $row, $user->getId());
            $sheet->setCellValue('B' . $row, $user->getFirstName());
            $sheet->setCellValue('C' . $row, $user->getLastName());
            $sheet->setCellValue('D' . $row, $user->getEmail());
            $sheet->setCellValue('E' . $row, implode(', ', $user->getRoles()));
            $sheet->setCellValue('F' . $row, $user->isBlocked() ? 'Blocked' : 'Active');

            // Apply alternating row colors
            $rowStyle = [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $row % 2 === 0 ? 'DDEBF7' : 'FFFFFF'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];

            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($rowStyle);
            $row++;
        }

        // Auto-size columns for better readability
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create a writer and save the file
        $writer = new Xlsx($spreadsheet);

        // Stream the file to the browser
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        // Set headers for file download
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="users_export.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/dashboard/user/delete/{id}', name: 'app_delete_user')]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); // Ensure only admins can delete users

        // Find the user by ID
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', 'User not found!');
            return $this->redirectToRoute('app_dashboard_users');
        }

        // Remove the user from the database
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User deleted successfully!');
        return $this->redirectToRoute('app_dashboard_users');
    }


    #[Route('/dashboard/update-user', name: 'app_update_user', methods: ['POST'])]
    public function updateUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Decode JSON request
        $data = json_decode($request->getContent(), true);

        // Validate input data
        if (!$data || !isset($data['id'], $data['firstName'], $data['lastName'], $data['email'], $data['roles'])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid data received'], 400);
        }

        // Find user by ID
        $user = $entityManager->getRepository(User::class)->find($data['id']);
        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'User not found'], 404);
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid email format'], 400);
        }

        // Ensure roles are formatted correctly
        if (!is_array($data['roles'])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid roles format'], 400);
        }

        // Update user fields
        $user->setFirstName(trim($data['firstName']));
        $user->setLastName(trim($data['lastName']));
        $user->setEmail(trim($data['email']));
        $user->setRoles($data['roles']); // Expecting an array of roles

        try {
            $entityManager->flush();
            return new JsonResponse(['status' => 'success', 'message' => 'User updated successfully']);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Database update failed'], 500);
        }
    }


    #[Route('/dashboard/admin/user-add', name: 'app_add_user', methods: ['POST'])]
    public function addUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Decode JSON request
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['message' => 'Invalid JSON data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Validate required fields
        if (empty($data['firstName']) || empty($data['lastName']) || empty($data['email']) || empty($data['roles'])) {
            return new JsonResponse(['message' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Check if user already exists
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['message' => 'Email already exists'], JsonResponse::HTTP_CONFLICT);
        }

        // Create new user
        $user = new User();
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);
        $user->setRoles($data['roles']); // Ensure roles are passed as an array

        // Set a default password (CHANGE THIS IN REAL USE)
        $defaultPassword = 'default123'; // Use a generated password or require user input
        $hashedPassword = $passwordHasher->hashPassword($user, $defaultPassword);
        $user->setPassword($hashedPassword);

        // Persist and save user
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User added successfully', 'userId' => $user->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function requestPasswordReset(
        Request                $request,
        EntityManagerInterface $entityManager,
        MailerInterface        $mailer,
        UrlGeneratorInterface  $urlGenerator,
        SessionInterface       $session,
        Environment            $twig // Correctly inject Twig service
    )
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $this->addFlash('error', 'If an account exists with that email, a password reset link has been sent.');
                return $this->redirectToRoute('app_home');
            }

            $resetToken = bin2hex(random_bytes(32));
            $expirationTime = new \DateTime('+10 minutes');

            $session->set('password_reset_token', $resetToken);
            $session->set('password_reset_expiration', $expirationTime);

            $resetUrl = $urlGenerator->generate('app_reset_password', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);

            // Render the email template using Twig
            $emailBody = $twig->render('emails/password_reset.html.twig', [
                'resetUrl' => $resetUrl,
            ]);

            // Create and send the email
            $emailMessage = (new Email())
                ->from('no-reply@yourdomain.com')
                ->to($user->getEmail())
                ->subject('Password Reset Request')
                ->html($emailBody);

            $mailer->send($emailMessage);

            $this->addFlash('success', 'If an account exists with that email, a password reset link has been sent.');
            return $this->redirectToRoute('app_home_signin');
        }

        return $this->render('password_reset/forgot_password.html.twig');
    }


    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SessionInterface $session, $token)
    {
        // Retrieve the reset token and expiration time from the session
        $storedToken = $session->get('password_reset_token');
        $expirationTime = $session->get('password_reset_expiration');

        if (!$storedToken || $storedToken !== $token || new \DateTime() > $expirationTime) {
            $this->addFlash('error', 'The reset token is invalid or has expired.');
            return $this->redirectToRoute('app_home_signin');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $request->get('email')]);

            if ($user) {
                // Use UserPasswordHasherInterface instead of UserPasswordEncoderInterface
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);

                $entityManager->flush();

                // Clear the session data
                $session->remove('password_reset_token');
                $session->remove('password_reset_expiration');

                $this->addFlash('success', 'Your password has been reset successfully.');
                return $this->redirectToRoute('app_home');
            } else {
                $this->addFlash('error', 'User not found.');
            }
        }

        return $this->render('password_reset/reset.html.twig');
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
    public function submitContactForm(Request $request, MailerInterface $mailer, Environment $twig): Response
    {


        // Get form data
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $phone = $request->request->get('phone');
        $project = $request->request->get('project');
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');

        // Simple Validation
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $this->addFlash('error', 'All required fields must be filled.');
            return $this->redirectToRoute('app_contact');
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Invalid email format.');
            return $this->redirectToRoute('app_contact');
        }

        // Simulate message processing (Save to DB or Send Email)
        try {
            // Render the email template
            $emailContent = $twig->render('emails/contact-email.html.twig', [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'project' => $project,
                'subject' => $subject,
                'message' => $message
            ]);

            // Create email
            $email = (new Email())
                ->from($email) // Sender's email
                ->to('culturespaceTeam@gmail.com') // Recipient's email
                ->subject('New Contact Form Submission: ' . $subject)
                ->html($emailContent);

            // Send email
            $mailer->send($email);

            $this->addFlash('success', 'Your message has been sent successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'There was an error sending your message: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contact');
    }

    #[Route('/2fa/verify', name: 'app_2fa_verify')]
    public function verify2fa(
        Request $request,
        MailerInterface $mailer,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        Environment $twig
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_home_signin');
        }

        // Check session data
        $storedCode = $session->get('2fa_code');
        $expiration = $session->get('2fa_expiration');
        $storedUserId = $session->get('2fa_user_id');

        // Check for explicit resend request
        $forceResend = $request->query->getBoolean('resend', false); // Use getBoolean for cleaner handling

        // Generate new code if:
        // - No code exists
        // - Code expired
        // - Explicit resend requested
        // - User ID mismatch
        if (!$storedCode || !$expiration || time() > $expiration || $forceResend || $storedUserId !== $user->getId()) {
            $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store code and expiration in session
            $session->set('2fa_code', $verificationCode);
            $session->set('2fa_expiration', (new \DateTime())->modify('+10 minutes')->getTimestamp());
            $session->set('2fa_user_id', $user->getId());

            // Send verification code via email
            try {
                $emailContent = $twig->render('emails/2fa_verification.html.twig', [
                    'code' => $verificationCode,
                    'user' => $user,
                ]);

                $email = (new Email())
                    ->from('no-reply@yourdomain.com')
                    ->to($user->getEmail())
                    ->subject('Your Verification Code')
                    ->html($emailContent);

                $mailer->send($email);

                if ($forceResend) {
                    $this->addFlash('success', 'A new verification code has been sent to your email.');
                } else {
                    $this->addFlash('info', 'Please check your email for your verification code.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to send verification code: ' . $e->getMessage());
                // Log the error for debugging
                // $this->logger->error('Email send failed: ' . $e->getMessage()); // Uncomment if you have a logger
            }
        } else {
            $this->addFlash('info', 'Please use the code already sent to your email.');
        }

        return $this->render('security/2fa_verify.html.twig', [
            'email' => $user->getEmail(),
        ]);
    }


    #[Route('/2fa/resend', name: 'app_2fa_resend')]
    public function resend2faCode(
        MailerInterface $mailer,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        Environment $twig
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_home_signin');
        }

        // Generate a new code
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Update session with new code
        $session->set('2fa_code', $verificationCode);
        $session->set('2fa_expiration', (new \DateTime())->modify('+10 minutes')->getTimestamp());
        $session->set('2fa_user_id', $user->getId());

        // Send the new code via email
        try {
            $emailContent = $twig->render('emails/2fa_verification.html.twig', [
                'code' => $verificationCode,
                'user' => $user,
            ]);

            $email = (new Email())
                ->from('no-reply@yourdomain.com')
                ->to($user->getEmail())
                ->subject('Your New Verification Code')
                ->html($emailContent);

            $mailer->send($email);

            $this->addFlash('success', 'A new verification code has been sent to your email.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to resend verification code: ' . $e->getMessage());
        }

        // Redirect back to the verification page
        return $this->redirectToRoute('app_2fa_verify');
    }
    /**
     * Verify the 2FA code submitted by user
     */
    #[Route('/2fa/confirm', name: 'app_2fa_confirm', methods: ['POST'])]
    public function confirm2fa(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_home_signin');
        }

        $submittedCode = $request->request->get('verification_code');
        $storedCode = $session->get('2fa_code');
        $expiration = $session->get('2fa_expiration');
        $storedUserId = $session->get('2fa_user_id');

        // Verify the code
        if (!$storedCode || !$expiration || !$storedUserId || $storedUserId !== $user->getId()) {
            $this->addFlash('error', 'Invalid verification session. Please try logging in again.');
            return $this->redirectToRoute('app_home_signin');
        }

        if (time() > $expiration) {
            $this->addFlash('error', 'Verification code has expired. Please request a new one.');
            $this->clear2faSession($session);
            return $this->redirectToRoute('app_2fa_verify');
        }

        if ($submittedCode !== $storedCode) {
            $this->addFlash('error', 'Invalid verification code.');
            return $this->redirectToRoute('app_2fa_verify'); // Don't generate new code here
        }

        // Code is valid, clear session and complete login
        $this->clear2faSession($session);
        $this->addFlash('success', 'Login successful!');

        // Redirect based on user role
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_dashboard_users');
        }
        return $this->redirectToRoute('app_home');
    }

    /**
     * Helper method to clear 2FA session data
     */
    private function clear2faSession(SessionInterface $session): void
    {
        $session->remove('2fa_code');
        $session->remove('2fa_expiration');
        $session->remove('2fa_user_id');
    }

    #[Route('/dashboard/users/export-pdf', name: 'app_export_users_pdf')]
    public function exportUsersPdf(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Fetch all users
        $users = $entityManager->getRepository(User::class)->findAll();

        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Culture Space');
        $pdf->SetTitle('User List Export');
        $pdf->SetSubject('User Management Report');
        $pdf->SetKeywords('Users, Report, Culture Space');

        // Set header data
        $pdf->SetHeaderData(
            $logo = '', // Add logo path here if desired
            $logo_width = 0,
            $title = 'Culture Space User Report',
            $string = "Generated on " . date('Y-m-d H:i:s')
        );

        // Set fonts
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

        // Set margins (adjusted for better fit)
        $pdf->SetMargins(15, 25, 15); // Left, Top, Right
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15); // Reduced bottom margin for more content per page

        // Set font
        $pdf->SetFont('helvetica', '', 9); // Slightly smaller font for better fit

        // Add a page
        $pdf->AddPage();

        // Calculate page width (A4 width = 210mm - margins)
        $pageWidth = 210 - 30; // 210mm - 15mm left - 15mm right = 180mm

        // Define column widths (total should equal pageWidth)
        $colWidths = [
            'id' => 15,      // Reduced from 20
            'firstName' => 35, // Reduced from 40
            'lastName' => 35,  // Reduced from 40
            'email' => 50,     // Kept at 50
            'roles' => 30,     // Reduced from 40
            'status' => 15     // Reduced from 30
        ];
        $totalWidth = array_sum($colWidths); // Should be 180

        // Header
        $pdf->SetFillColor(79, 129, 189); // Blue header
        $pdf->SetTextColor(255, 255, 255); // White text
        $pdf->Cell($colWidths['id'], 10, 'ID', 1, 0, 'C', 1);
        $pdf->Cell($colWidths['firstName'], 10, 'First Name', 1, 0, 'C', 1);
        $pdf->Cell($colWidths['lastName'], 10, 'Last Name', 1, 0, 'C', 1);
        $pdf->Cell($colWidths['email'], 10, 'Email', 1, 0, 'C', 1);
        $pdf->Cell($colWidths['roles'], 10, 'Roles', 1, 0, 'C', 1);
        $pdf->Cell($colWidths['status'], 10, 'Status', 1, 1, 'C', 1);

        // Body
        $pdf->SetFillColor(245, 245, 245); // Light gray for alternating rows
        $pdf->SetTextColor(0, 0, 0); // Black text
        $fill = 0;

        foreach ($users as $user) {
            // Use MultiCell for wrapping long content
            $pdf->MultiCell($colWidths['id'], 10, $user->getId(), 1, 'C', $fill, 0);
            $pdf->MultiCell($colWidths['firstName'], 10, $user->getFirstName(), 1, 'L', $fill, 0);
            $pdf->MultiCell($colWidths['lastName'], 10, $user->getLastName(), 1, 'L', $fill, 0);
            $pdf->MultiCell($colWidths['email'], 10, $user->getEmail(), 1, 'L', $fill, 0);
            $pdf->MultiCell($colWidths['roles'], 10, implode(', ', $user->getRoles()), 1, 'L', $fill, 0);
            $pdf->MultiCell($colWidths['status'], 10, $user->isBlocked() ? 'Blocked' : 'Active', 1, 'C', $fill, 1);

            $fill = !$fill; // Toggle fill for alternating rows
        }

        // Output the PDF as a streamed response
        $pdfContent = $pdf->Output('users_export.pdf', 'S'); // 'S' returns the PDF as a string

        $response = new StreamedResponse(function () use ($pdfContent) {
            echo $pdfContent;
        });

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename="users_export.pdf"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }



    #[Route('/dashboard/users/filter', name: 'app_filter_users', methods: ['GET'])]
    public function filterUsers(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $filter = trim($request->query->get('filter', ''));

        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        if (!empty($filter)) {
            $filterParam = '%' . $filter . '%';
            $queryBuilder
                ->where('CONCAT(u.firstName, \' \', u.lastName) LIKE :filter')
                ->orWhere('u.email LIKE :filter')
                ->orWhere('u.roles LIKE :filter')
                ->setParameter('filter', $filterParam);
        }

        $queryBuilder->orderBy('u.id', 'ASC');
        $users = $queryBuilder->getQuery()->getResult();

        $userData = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(), // Raw roles for JS to convert
                'isBlocked' => $user->isBlocked(),
                'profileIMG' => $user->getProfileIMG(),
            ];
        }, $users);

        return new JsonResponse(['users' => $userData]);
    }
}

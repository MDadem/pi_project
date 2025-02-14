<?php

namespace App\Controller;


use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
final class UserController extends AbstractController {

    #[Route('/dashboard/edit-profile/{id}', name: 'app_edit_profile')]
    public function edit(User $user, Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
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
            return $this->redirectToRoute('app_edit_profile', ['id' => $user->getId()]);
        }

        return $this->render('backend/user/edit_profile.html.twig', [
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

}

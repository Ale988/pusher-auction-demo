<?php

namespace App\Controller;

use Pusher\Pusher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuctionController extends AbstractController
{
    private Pusher $pusher;

    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    #[Route('/auction', name: 'auction_page', methods: ['GET'])]
    public function auctionPage(): Response
    {
        return $this->render('auction.html.twig', [
            'pusher_key' => $this->getParameter('pusher_app_key'),
            'pusher_cluster' => $this->getParameter('pusher_app_cluster'),
        ]);
    }

    #[Route('/auction/bid', name: 'auction_bid', methods: ['POST'])]
    public function bid(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['username']) || !isset($data['amount'])) {
                throw new \InvalidArgumentException('Missing required fields');
            }

            $username = trim(htmlspecialchars($data['username']));
            $amount = (float) $data['amount'];

            if (empty($username)) {
                throw new \InvalidArgumentException('Username cannot be empty');
            }

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Bid amount must be greater than 0');
            }

            $eventData = [
                'username' => $username,
                'amount' => $amount,
            ];

            $this->pusher->trigger('auction-channel', 'new-bid', $eventData);

            return new JsonResponse(['status' => 'success', 'data' => $eventData]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An unexpected error occurred'], 500);
        }
    }
}
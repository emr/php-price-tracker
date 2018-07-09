<?php

namespace App\Controller;

use App\Exception\InvalidUrlException;
use App\Exception\ProductNotFoundException;
use App\Exception\UnsupportedSiteException;
use App\Tracker\TrackerFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrackController extends Controller
{
    /**
     * @Route("/api/track", name="track", methods={"post"})
     */
    public function trackAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $serializer = $this->get('serializer');
        $trackingManager = $this->get('app.tracking_manager');

        $status = Response::HTTP_BAD_REQUEST;
        $product = null;
        $error = true;

        try {
            if (empty($url = $request->request->get('url', null)))
                throw new \InvalidArgumentException('There is no URL data.');

            if (empty($interval = $request->request->get('interval', null)))
                throw new \InvalidArgumentException('There is no interval value.');

            $tracker = TrackerFactory::createFromURL($url);
            $product = $tracker->fetchProduct();

            $product->setUser($this->getUser());
            $product->setIntervalTime($interval);

            $em->persist($product);
            $em->flush();

            $trackingManager->restartTracking();

            $status = Response::HTTP_CREATED;

            $message = 'The product is tracking now.';
            $error = false;
        }
        catch (InvalidUrlException $e)
        {
            $message = 'URL is invalid!';
        }
        catch (UnsupportedSiteException $e)
        {
            $message = 'This site is not supported!';
        }
        catch (ProductNotFoundException $e)
        {
            $message = 'The product was not found in page!';
        }
        catch (ConnectException $e)
        {
            $message = sprintf("Could not connect host '%s'", parse_url($url)['host']);
        }
        catch (RequestException $e)
        {
            $message = sprintf("Could not send request host '%s'", parse_url($url)['host']);
        }
        catch (\InvalidArgumentException $e)
        {
            $message = $e->getMessage();
        }
        catch (\Exception $e)
        {
            $message = $e->getMessage();
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return new JsonResponse([
            ($error ? 'error' : 'success') => $message,
            'product' => $error ? null : $serializer->normalize($product),
        ], $status);
    }
}
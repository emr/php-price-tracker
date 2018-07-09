<?php

namespace App\Service;

use Swift_Mailer as Mailer;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;
use App\DBAL\Types\NotificationRuleDirectionType;
use App\DBAL\Types\NotificationRuleUnitType;
use App\Entity\NotificationRule;
use App\Entity\Price;
use App\Entity\Product;

class NotificationManager
{
    /** @var Mailer */
    private $mailer;

    /** @var EngineInterface */
    private $twig;

    /** @var TranslatorInterface */
    private $translator;

    /** @var string */
    private $templateFile;

    public function __construct(Mailer $mailer, EngineInterface $twig, TranslatorInterface $translator, string $templateFile)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->templateFile = $templateFile;
    }

    /**
     * Notify users if necessary
     * @param Product $product
     * @param callable|null $onSuccess
     */
    public function notify(Product $product, ?callable $onSuccess = null): void
    {
        $prices = $product->getPrices();

        // break if price count is not enough for compare
        if ($prices->count() < 2)
            return;

        if (!$user = $product->getUser())
            return;

        if ($notificationRule = $this->getNotificationRuleIfPriceChanged($product))
            if (!$notificationRule->isNotified())
            {
                $message = (new \Swift_Message())
                    ->setSubject(
                        $this->translator->trans('notification.title', [
                            'product.title' => $product->getTitle(),
                            'user.name' => $user->getName(),
                            'user.username' => $user->getUsername(),
                        ])
                    )
                    ->setBody($this->renderTemplate($notificationRule->getProduct()))
                    ->setContentType('text/html')
                ;

                if ($this->mailer->send($message))
                {
                    $notificationRule->setNotified(true);

                    $onSuccess($product);
                }
            }
    }

    /**
     * @throws \InvalidArgumentException
     * @param Product $product
     * @return null|NotificationRule
     */
    private function getNotificationRuleIfPriceChanged(Product $product): ?NotificationRule
    {
        $newPrice = $product->getPrices()->first();
        $oldPrice = $product->getPrices()->next();

        $changed = null;

        foreach ($product->getNotificationRules() as $notificationRule)
        {
            $change = 0;

            switch ($notificationRule->getUnit())
            {
                case NotificationRuleUnitType::AMOUNT:
                    $change = $newPrice->getPrice() - $oldPrice->getPrice();
                    break;
                case NotificationRuleUnitType::PERCENT:
                    $change = (100 * $newPrice->getPrice() / $oldPrice->getPrice()) - 100;
                    break;
            }

            switch ($notificationRule->getDirection())
            {
                case NotificationRuleDirectionType::EXPENSIVE:
                    $changed = $change > 0;
                    break;
                case NotificationRuleDirectionType::CHEAP:
                    $changed = $change < 0;
                    break;
            }

            if ($changed)
            {
                $changed = $notificationRule;
                break;
            }
        }

        return $changed;
    }

    /**
     * @param Product $product
     * @return string
     */
    private function renderTemplate(Product $product)
    {
        return $this->twig->render($this->templateFile, ['product' => $product]);
    }
}
<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Http\Controllers;

use App\DTO\EnhancedStat;

final class AssistantPanelController extends AssistantBaseController
{
    /**
     * @return EnhancedStat[]
     */
    protected function getModuleStats(): array
    {
        $user = $this->getAuthenticatedUser();

        return $this->statsService->getPanelStats(
            $this->getModuleSlug(),
            $user
        );
    }
}

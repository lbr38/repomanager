<svg class="donut" width="163" viewBox="0 0 100 100">
    <circle style="stroke-dasharray: <?=$diskUsedSpacePercent?> 100;" cx="50" cy="50" r="40" stroke="<?=$donutColor?>" stroke-width="6" fill="none" pathLength="100" transform="rotate(-90 50 50)"/>
    <circle cx="50" cy="50" r="43" stroke="rgb(139, 138, 175)" stroke-width="0.2" fill="none" />
    <circle cx="50" cy="50" r="37" stroke="rgb(139, 138, 175)" stroke-width="0.2" fill="none" />
    <text aria-hidden="true" tabindex="-1" x="50" y="50"><?=$diskUsedSpacePercent?>%</text>
</svg>
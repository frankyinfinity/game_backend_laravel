<?php

namespace App\Custom\Draw\Complex\Table;

class TableDraw {

    const ALIGN_LEFT = 'left';
    const ALIGN_CENTER = 'center';
    const ALIGN_RIGHT = 'right';

    private string $uid;
    private int $x = 0;
    private int $y = 0;
    private int $width = 0;
    private int $rowHeight = 40;
    private array $heads = [];
    private array $rows = [];
    private array $drawItems = [];
    private int $bottomY = 0;

    public function __construct(string $uid) {
        $this->uid = $uid;
    }

    public function setOrigin(int $x, int $y) {
        $this->x = $x;
        $this->y = $y;
    }

    public function setWidth(int $width) {
        $this->width = $width;
    }

    public function setRowHeight(int $height) {
        $this->rowHeight = $height;
    }

    public function addHead(TableHeadDraw $head) {
        $this->heads[] = $head;
    }

    public function addRow(array $row) {
        $this->rows[] = $row;
    }

    public function getDrawItems(): array {
        return $this->drawItems;
    }

    public function getBottomY(): int {
        return $this->bottomY;
    }

    public function build() {
        $this->drawItems = [];
        $currentX = $this->x;
        $currentY = $this->y;
        $maxHeaderHeight = 0;

        if ($this->width > 0 && !empty($this->heads)) {
            $totalHeaderWidth = 0;
            foreach ($this->heads as $head) {
                $totalHeaderWidth += $head->getWidth();
            }

            if ($totalHeaderWidth > 0) {
                foreach ($this->heads as $head) {
                    $newWidth = ($head->getWidth() / $totalHeaderWidth) * $this->width;
                    $head->setSize((int)$newWidth, $head->getHeight());
                }
            }
        }

        // Build Headers
        foreach ($this->heads as $head) {
            $headItems = $head->build($currentX, $currentY);
            foreach ($headItems as $item) {
                $this->drawItems[] = $item;
            }
            if ($head->getHeight() > $maxHeaderHeight) {
                $maxHeaderHeight = $head->getHeight();
            }
            $currentX += $head->getWidth();
        }

        $currentY += $maxHeaderHeight;

        // Build Rows
        foreach ($this->rows as $row) {
            $currentX = $this->x;
            $maxRowHeight = 0;

            foreach ($row as $index => $cell) {
                if (isset($this->heads[$index])) {
                    // Match cell width to corresponding header width
                    $cell->setSize($this->heads[$index]->getWidth(), $cell->getHeight());
                }

                $cellItems = $cell->build($currentX, $currentY);
                foreach ($cellItems as $item) {
                    $this->drawItems[] = $item;
                }
                
                if ($cell->getHeight() > $maxRowHeight) {
                    $maxRowHeight = $cell->getHeight();
                }

                $currentX += $cell->getWidth();
            }
            $currentY += max($maxRowHeight, $this->rowHeight);
        }
        $this->bottomY = $currentY;
    }
}

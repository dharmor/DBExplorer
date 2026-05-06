<?php
declare(strict_types=1);

class FPDF
{
    public function __construct(...$args) {}
    public function AliasNbPages(): void {}
    public function SetAutoPageBreak(...$args): void {}
    public function SetMargins(...$args): void {}
    public function AddPage(): void {}
    public function SetFont(...$args): void {}
    public function SetTextColor(...$args): void {}
    public function SetFillColor(...$args): void {}
    public function SetDrawColor(...$args): void {}
    public function Rect(...$args): void {}
    public function SetXY(...$args): void {}
    public function SetY(...$args): void {}
    public function Cell(...$args): void {}
    public function MultiCell(...$args): void {}
    public function Ln(...$args): void {}
    public function Line(...$args): void {}
    public function GetX(): float { return 10.0; }
    public function GetY(): float { return 10.0; }
    public function GetPageWidth(): float { return 279.0; }
    public function GetPageHeight(): float { return 216.0; }
    public function PageNo(): int { return 1; }
    public function Output(string $dest = 'I', string $name = 'document.pdf'): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        echo "%PDF-1.1\n1 0 obj <<>> endobj\ntrailer <<>>\n%%EOF";
    }
}

<?php
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

class PDF extends FPDF
{
    private $title;

    function SetTitle($title, $isUTF8=false) {
        parent::SetTitle($title, $isUTF8);
        $this->title = $title;
    }

    // Page header
    function Header()
    {
        // Linha colorida no topo para um toque de design
        $this->SetFillColor(33, 37, 41); // Cor escura (bg-dark)
        $this->Rect(0, 0, $this->GetPageWidth(), 2, 'F');

        // Título do Documento
        $this->SetY(10);
        $this->SetFont('Arial','B',18);
        $this->SetTextColor(33, 37, 41); // Texto escuro (quase preto)
        $this->Cell(0, 10, $this->TextToCell($this->title), 0, 1, 'C');

        // Linha cinza abaixo do título para separar o cabeçalho do conteúdo
        $this->SetDrawColor(200, 200, 200); // Cinza claro para a linha
        $this->Line(15, $this->GetY() + 2, $this->GetPageWidth() - 15, $this->GetY() + 2);

        // Posição para o início do conteúdo
        $this->SetY($this->GetY() + 10);
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','',8);
        // Page number
        $this->Cell(0,10, $this->TextToCell('Página ') . $this->PageNo().'/{nb}',0,0,'C');
    }

    // Função para converter texto para a codificação do PDF
    function TextToCell($text) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }

    // Função para desenhar uma célula de status colorida
    function StatusCell($w, $h, $status, $data_vencimento)
    {   
        $text = 'Pendente';
        // Cores em RGB
        $fillColor = [255, 193, 7];   // Amarelo (Warning)
        $textColor = [0, 0, 0];       // Preto

        if ($status === 'pago') {
            $text = 'Pago';
            $fillColor = [25, 135, 84];   // Verde (Success)
            $textColor = [255, 255, 255]; // Branco
        } else {
            $hoje = new DateTime();
            $vencimento = new DateTime($data_vencimento);
            // Compara apenas a data, ignorando a hora
            if ($vencimento < $hoje->setTime(0, 0, 0)) {
                $text = 'Atrasado';
                $fillColor = [220, 53, 69];   // Vermelho (Danger)
                $textColor = [255, 255, 255]; // Branco
            }
        }

        // Posição atual
        $x = $this->GetX();
        $y = $this->GetY();

        // Define as cores para a célula de status
        $this->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        $this->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

        // Desenha o retângulo com cantos totalmente arredondados (efeito de pílula)
        $this->RoundedRect($x, $y, $w, $h, $h/2, 'F');

        // Centraliza o texto dentro da pílula
        $textWidth = $this->GetStringWidth($text);
        $textX = $x + ($w - $textWidth) / 2;
        $this->Text($textX, $y + $h/1.5, $this->TextToCell($text));

        // Reseta a cor do texto
        $this->SetTextColor(0, 0, 0);
    }

    // Função para desenhar retângulos com cantos arredondados
    function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k));
        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k));
        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-($y+$r))*$k));
        $this->_Arc($x - $r, $y + $r - $r*$MyArc, $x - $r*$MyArc, $y, $x, $y);
        $this->_out($op);
    }

    // --- Funções para a faixa diagonal ---

    protected $angle = 0;

    function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1)
            $x = $this->x;
        if ($y == -1)
            $y = $this->y;
        if ($this->angle != 0)
            $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    function DiagonalBanner($text, $status)
    {
        // Define cores com base no status
        switch ($status) {
            case 'pago':
                $fillColor = [0, 150, 136]; // Verde Teal mais vivo
                break;
            case 'atrasado':
                $fillColor = [244, 67, 54]; // Vermelho Material Design
                break;
            default:
                $fillColor = [255, 152, 0]; // Laranja Amber
                break;
        }

        // Desenha o triângulo de fundo
        $this->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        $this->Polygon([0, 0, 0, 50, 50, 0], 'F');

        // Configura a fonte para o texto da faixa
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(255, 255, 255);

        // Rotaciona e posiciona o texto
        $this->Rotate(-45, 12, 30);
        $this->Text(12, 30, $this->TextToCell($text));
        $this->Rotate(0); // Reseta a rotação
    }

    function Polygon($points, $style = 'D')
    {
        // Desenha um polígono
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';

        $h = $this->h;
        $k = $this->k;

        $points_str = '';
        for($i=0;$i<count($points);$i+=2){
            $points_str .= sprintf('%.2F %.2F', $points[$i]*$k, ($h-$points[$i+1])*$k) . ($i==0 ? ' m ' : ' l ');
        }
        $this->_out($points_str . $op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1*$this->k, ($h-$y1)*$this->k, $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
}
?>

<?php
declare(strict_types=1);

namespace App\Core\Latte;

use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\BlockMacros;
use Latte\PhpWriter;

class SideDialogMacroSet extends BlockMacros
{
    private BlockMacros $originalBlock;

    public static function install(Compiler $compiler): void
    {
        $macroBlock = new static($compiler);
        $macroBlock->addMacro(
            'sideDialog',
            [$macroBlock, 'sideDialogSnippetStart'],
            [$macroBlock, 'sideDialogSnippetEnd']
        );
        $macroBlock->originalBlock = current($compiler->getMacros()['snippet']);
    }

    public function sideDialogSnippetStart(MacroNode $node, PhpWriter $writer): string
    {
        // act like {snippet side-dialog}
        $node->name = 'snippet';
        $node->setArgs('side-dialog');
        $result = $this->originalBlock->macroSnippet($node, $writer);
        // switch name back otherwise compiler would search closing macro {/snippet} instead of {/sideDialog}
        $node->name = 'sideDialog';
        return $result;
    }

    public function sideDialogSnippetEnd(MacroNode $node, PhpWriter $writer): void
    {
        // act like closing {/snippet}
        $node->name = 'snippet';
        $this->originalBlock->macroBlockEnd($node, $writer);
        $node->name = 'sideDialog';
    }
}

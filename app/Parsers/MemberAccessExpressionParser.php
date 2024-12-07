<?php

namespace App\Parsers;

use App\Contexts\Argument;
use App\Contexts\AssignmentValue;
use App\Contexts\AbstractContext;
use App\Contexts\MethodCall;
use App\Parser\SourceFile;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\QualifiedName;

class MemberAccessExpressionParser extends AbstractParser
{
    /**
     * @var MethodCall
     */
    protected AbstractContext $context;

    public function parse(MemberAccessExpression $node)
    {
        $this->context->name = $node->memberName->getFullText(SourceFile::fullText());

        foreach ($node->getDescendantNodes() as $child) {
            if ($child instanceof QualifiedName) {
                $this->context->class ??= (string) $child->getResolvedName();

                return $this->context;
            }

            if ($child instanceof Variable) {
                if ($child->getName() === 'this') {
                    dd('got this');
                    $propName = $child->getParent()->memberName->getFullText($this->sourceFile->getFileContents());

                    $result = $this->context->searchForProperty($propName);

                    if ($result) {
                        $this->context->classUsed = $result['types'][0] ?? null;
                    }

                    continue;
                }

                $varName = $child->getName();

                $result = $this->context->searchForVar($varName);

                if ($result instanceof AssignmentValue) {
                    $this->context->class = $result->getValue()['name'];
                } else {
                    $this->context->class = $result;
                }
            }
        }

        return $this->context;
    }

    public function initNewContext(): ?AbstractContext
    {
        if (!($this->context instanceof MethodCall)) {
            return new MethodCall;
        }

        return null;
    }
}
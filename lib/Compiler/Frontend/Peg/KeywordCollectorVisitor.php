<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend\Peg;

class KeywordCollectorVisitor {

    public function __construct(array $keywords, array $softKeywords) {
    }

/*
    class KeywordCollectorVisitor(GrammarVisitor):
        """Visitor that collects all the keywods and soft keywords in the Grammar"""

        def __init__(self, gen: "ParserGenerator", keywords: Dict[str, int], soft_keywords: Set[str]):
            self.generator = gen
            self.keywords = keywords
            self.soft_keywords = soft_keywords

        def visit_StringLeaf(self, node: StringLeaf) -> None:
            val = ast.literal_eval(node.value)
            if re.match(r"[a-zA-Z_]\w*\Z", val):  # This is a keyword
                if node.value.endswith("'") and node.value not in self.keywords:
                    self.keywords[val] = self.generator.keyword_type()
                else:
                    return self.soft_keywords.add(node.value.replace('"', ""))
     */
}
<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Compiler\Frontend;

// https://en.wikipedia.org/wiki/Earley_parser
class EarleyParser {
    public function __invoke($context): IProgramNode {
        //$tokens = [];
        $program = new ProgramNode();
        /*
        while ($token = $this->lexer->nextToken()) {
            $tokens[] = $token;
            if ($this->addNode($program, $tokens)) {
                $tokens = []; // reset tokens after adding a new language construct
            }
        }
        */
        return $program;
    }
    /*
        private function addNode(IProgram $program, array $tokens): bool {
            return false;
        }*/

    /*
      DECLARE ARRAY S;

    function INIT(words)
        S ← CREATE-ARRAY(LENGTH(words) + 1)
        for k ← from 0 to LENGTH(words) do
            S[k] ← EMPTY-ORDERED-SET

    function EARLEY-PARSE(words, grammar)
        INIT(words)
        ADD-TO-SET((γ → •S, 0), S[0])
        for k ← from 0 to LENGTH(words) do
            for each state in S[k] do  // S[k] can expand during this loop
                if not FINISHED(state) then
                    if NEXT-ELEMENT-OF(state) is a nonterminal then
                        PREDICTOR(state, k, grammar)         // non-terminal
                    else do
                        SCANNER(state, k, words)             // terminal
                else do
                    COMPLETER(state, k)
            end
        end
        return chart

    procedure PREDICTOR((A → α•Bβ, j), k, grammar)
        for each (B → γ) in GRAMMAR-RULES-FOR(B, grammar) do
            ADD-TO-SET((B → •γ, k), S[k])
        end

    procedure SCANNER((A → α•aβ, j), k, words)
        if a ⊂ PARTS-OF-SPEECH(words[k]) then
            ADD-TO-SET((A → αa•β, j), S[k+1])
        end

    procedure COMPLETER((B → γ•, x), k)
        for each (A → α•Bβ, j) in S[x] do
            ADD-TO-SET((A → αB•β, j), S[k])
    end
         */
}
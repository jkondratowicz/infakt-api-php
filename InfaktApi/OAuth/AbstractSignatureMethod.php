<?php

namespace InfaktApi\OAuth;

/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
abstract class AbstractSignatureMethod {

    /**
     * Needs to return the name of the Signature Method (ie HMAC-SHA1)
     * @return string
     */
    abstract public function get_name();

    /**
     * Build up the signature
     * NOTE: The output of this function MUST NOT be urlencoded.
     * the encoding is handled in OAuthRequest when the final
     * request is serialized
     * @param Request $request
     * @param OAuth\Consumer $consumer
     * @param Token $token
     * @return string
     */
    abstract public function build_signature($request, $consumer, $token);

    /**
     * Verifies that a given signature is correct
     * @param Request $request
     * @param OAuth\Consumer $consumer
     * @param Token $token
     * @param string $signature
     * @return bool
     */
    public function check_signature($request, $consumer, $token, $signature) {
        $built = $this->build_signature($request, $consumer, $token);
        return $built == $signature;
    }

}

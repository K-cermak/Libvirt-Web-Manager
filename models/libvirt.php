<?php
    function getList($connectionUri) {
        $states = [
            0 => "nostate",
            1 => "running",
            2 => "blocked",
            3 => "paused",
            4 => "shutdown",
            5 => "shutoff",
            6 => "crashed",
            7 => "pmsuspended"
        ];
    
        try {
            $conn = libvirt_connect($connectionUri, false);
            if (!$conn) {
                return null;
            }

            $doms = libvirt_list_domains($conn);
            if (!$doms) {
                return null;
            }

            $ret = [];
            foreach ($doms as $domName) {
                $domResource = libvirt_domain_lookup_by_name($conn, $domName);
                if (!$domResource) {
                    return null;
                }

                $info = libvirt_domain_get_info($domResource);
                if (!$info) {
                    return null;
                }

                $ret[$domName] = [
                    "state" => $states[$info["state"]],
                    "memory" => $info["memory"],
                    "cpus" => $info["nrVirtCpu"]
                ];
            }
        } catch (Exception $e) {
            return null;
        }

        return $ret;
    }

    function startDomain($connectionUri, $domain) {
        try {
            $conn = libvirt_connect($connectionUri, false);
            if (!$conn) {
                return null;
            }

            $domResource = libvirt_domain_lookup_by_name($conn, $domain);
            if (!$domResource) {
                return null;
            }

            $ret = libvirt_domain_create($domResource);
        } catch (Exception $e) {
            return null;
        }

        return $ret;
    }

    function shutdownDomain($connectionUri, $domain) {
        try {
            $conn = libvirt_connect($connectionUri, false);
            if (!$conn) {
                return null;
            }

            $domResource = libvirt_domain_lookup_by_name($conn, $domain);
            if (!$domResource) {
                return null;
            }

            $ret = libvirt_domain_shutdown($domResource);
        } catch (Exception $e) {
            return null;
        }

        return $ret;
    }

    function suspendDomain($connectionUri, $domain) {
        try {
            $conn = libvirt_connect($connectionUri, false);
            if (!$conn) {
                return null;
            }

            $domResource = libvirt_domain_lookup_by_name($conn, $domain);
            if (!$domResource) {
                return null;
            }

            $ret = libvirt_domain_suspend($domResource);
        } catch (Exception $e) {
            return null;
        }

        return $ret;
    }

    function resumeDomain($connectionUri, $domain) {
        try {
            $conn = libvirt_connect($connectionUri, false);
            if (!$conn) {
                return null;
            }

            $domResource = libvirt_domain_lookup_by_name($conn, $domain);
            if (!$domResource) {
                return null;
            }

            $ret = libvirt_domain_resume($domResource);
        } catch (Exception $e) {
            return null;
        }

        return $ret;
    }

    function destroyDomain($connectionUri, $domain) {
        try {
            $conn = libvirt_connect($connectionUri, false);
            if (!$conn) {
                return null;
            }

            $domResource = libvirt_domain_lookup_by_name($conn, $domain);
            if (!$domResource) {
                return null;
            }

            $ret = libvirt_domain_destroy($domResource);
        } catch (Exception $e) {
            return null;
        }

        return $ret;
    }

    function getDisks($connectionUri, $domain) {
        try {
            $conn = libvirt_connect($connectionUri, false);
            if (!$conn) {
                return null;
            }

            $domResource = libvirt_domain_lookup_by_name($conn, $domain);
            if (!$domResource) {
                return null;
            }

            $xml = libvirt_domain_get_xml_desc($domResource);
            if (!$xml) {
                return null;
            }

            $ret = drivesParse($xml);
        } catch (Exception $e) {
            return null;
        }

        return $ret;
    }


    function drivesParse($xmlString) {
        $doc = new DOMDocument();
        $doc->loadXML($xmlString);
    
        $xpath = new DOMXPath($doc);
        $drives = $xpath->query("//devices/disk[@device='disk']");
    
        $result = [];
    
        foreach ($drives as $drive) {
            $target = $drive->getElementsByTagName("target")->item(0);
            $source = $drive->getElementsByTagName("source")->item(0);
    
            if ($target) {
                $name = $target->getAttribute("dev");
            } else {
                $name = null;
            }
    
            if ($source) {
                $path = $source->getAttribute("file") ? : $source->getAttribute("dev") ?: null;
            } else {
                $path = null;
            }
    
            if ($name && $path) {
                $result[] = [
                    "name" => $name,
                    "path" => $path
                ];
            }
        }
    
        return $result;
    }

    function unplugDisk($connectionUri, $domainName, $disk) {
        $conn = libvirt_connect($connectionUri, false);
        if (!$conn) {
            return null;
        }
    
        $dom = libvirt_domain_lookup_by_name($conn, $domainName);
        if (!$dom) {
            return null;
        }
    
        $xml = libvirt_domain_get_xml_desc($dom, 0);
        if (!$xml) {
            return null;
        }
    
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $xpath = new DOMXPath($doc);
    
        $drives = $xpath->query("//devices/disk[@device='disk']");
        $diskXml = null;
    
        foreach ($drives as $drive) {
            $target = $drive->getElementsByTagName("target")->item(0);
            if ($target && $target->getAttribute("dev") === $disk) {
                $diskXml = $doc->saveXML($drive);
                $drive->parentNode->removeChild($drive);
                break;
            }
        }
    
        if (!$diskXml) {
            return null;
        }
        
        $detachSuccess = libvirt_domain_detach_device($dom, $diskXml);
        if (!$detachSuccess) {
            return null;
        }
    
        libvirt_domain_define_xml($conn, $doc->saveXML());
    
        return true;
    }

    function plugDisk($connectionUri, $domain, $drivePath, $driveName, $driveType) {
        $conn = libvirt_connect($connectionUri, false);
        if (!$conn) {
            return null;
        }

        $domResource = libvirt_domain_lookup_by_name($conn, $domain);
        if (!$domResource) {
            return null;
        }

        $diskXml = "<disk type='file' device='disk'>
                    <driver name='qemu' type='qcow2'/>
                    <source file='$drivePath'/>
                    <target dev='$driveName' bus='$driveType'/>
                </disk>";

        $ret = libvirt_domain_attach_device($domResource, $diskXml, 0);
        if (!$ret) {
            return null;
        }

        /*$xml = libvirt_domain_get_xml_desc($domResource, 0);
        if (!$xml) {
            return null;
        }

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xml);
    
        $devicesNode = $doc->getElementsByTagName("devices")->item(0);
        if (!$devicesNode) {
            return null;
        }

        $newDisk = new DOMDocument();
        $newDisk->loadXML($diskXml);
        $importedDisk = $doc->importNode($newDisk->documentElement, true);

        $devicesNode->appendChild($importedDisk);

        $defineResult = libvirt_domain_define_xml($conn, $doc->saveXML());
        if (!$defineResult) {
            return null;
        }*/

        return true;
    }

?>
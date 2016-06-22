#!/usr/bin/env python3
# coding=utf-8
import csv
import logging
from os import listdir
from os.path import exists, join, isdir, basename
from typing import Dict
from typing import List
from typing import Set

from benchmark.data.misuse import Misuse
from benchmark.utils import csv_util


class Grouping:
    def get(self, misuse: Misuse) -> str:
        raise NotImplementedError


class Visualizer:
    def __init__(self, results_base_path: str, reviewed_eval_result_file: str, result_file: str, data_path: str):
        self.results_base_path = results_base_path
        self.reviewed_eval_result_file = reviewed_eval_result_file
        self.result_file = result_file
        self.data_path = data_path
        self.detector_header = 'Detector'

    def create(self):
        logger = logging.getLogger("visualize")

        if not exists(self.results_base_path):
            logger.error("No results found.")
            return

        results = dict()  # type: Dict[str, Dict[str, str]]
        misuses = set()  # type: Set[str]

        for detector_result_dir in self.__get_immediate_subdirs(self.results_base_path):
            logger.info("Adding results: %s", detector_result_dir)
            reviewed_eval_result_file = join(detector_result_dir, self.reviewed_eval_result_file)
            if not exists(reviewed_eval_result_file):
                continue

            detector_result = dict()  # type: Dict[str, str]
            with open(reviewed_eval_result_file) as reviewed_eval_result:
                csvreader = csv.reader(reviewed_eval_result, quotechar='|')
                for row in csvreader:
                    misuse, result = row
                    detector_result[misuse] = result
                    misuses.add(misuse)

            detector = basename(detector_result_dir)
            results[detector] = detector_result

        if not results:
            logger.error("No results found.")
            return

        headers = [self.detector_header] + sorted(list(misuses))
        file = join(self.results_base_path, self.result_file)
        csv_util.write_table(file, headers, results)

    def group(self, target_file: str, grouping: Grouping):
        logger = logging.getLogger("visualize-group")

        results = csv_util.read_table(join(self.results_base_path, self.result_file),
                                      self.detector_header)  # type: Dict[str, Dict[str, str]]

        grouped_results = dict()  # type: Dict[str, Dict[str, str]]
        groups = set()  # type: Set[str]

        for detector, results_per_misuse in results.items():
            logger.info("Grouping results for detector %s", detector)
            grouped_results[detector] = dict()
            results_per_group = dict()  # type: Dict[str, List[int]]
            for misuse_name, result_as_str in results_per_misuse.items():
                misuse_path = join(self.data_path, misuse_name)
                if not Misuse.ismisuse(misuse_path):
                    logger.error("Couldn't validate misuse %s", misuse_name)
                    continue

                result = int(result_as_str)  # type: int
                group = grouping.get(Misuse(misuse_path))

                if group not in results_per_group:
                    results_per_group[group] = [result]
                else:
                    results_per_group[group].append(result)

                groups.add(group)

            for group, results_for_group in results_per_group.items():
                average = sum(results_for_group) / len(results_for_group)
                grouped_results[detector][group] = average

        headers = [self.detector_header] + sorted(list(groups))
        file = join(self.results_base_path, target_file)
        csv_util.write_table(file, headers, grouped_results)

    @staticmethod
    def __get_immediate_subdirs(directory: str):
        return [join(directory, subdir) for subdir in listdir(directory) if isdir(join(directory, subdir))]
